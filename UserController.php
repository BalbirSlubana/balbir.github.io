<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Auth;
use Illuminate\Support\Facades\DB;
use Session;
use App\lead;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;

class UserController extends Controller
{
    public function getSignup(){
        $roles = DB::table('role')->select('id','value')->get();
        return view('user.signup',['roles' => $roles]);
    }
    public function postSignup(Request $request){
        $this->validate($request,[
            'username' => 'required|min:4',
            'email' => 'email|required|unique:users',
            'password' => 'required|min:4',
            'role' => 'required|min:1'
        ]);

        $user = new User([
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'role' => $request->input('role')
        ]);
        $user->save();
        Auth::login($user);
		if(Auth::user()->status == "1"){
			$status = "Issued";
		}else{
			$status = "Revoke";
		}
        return redirect()->route('user.profile')->with(['role' => DB::table('role')->select('value')->where('id', Auth::user()->role)->get(), 'username' => Auth::user()->username, 'email' => Auth::user()->email, 'status' => $status, 'created_at' => Auth::user()->created_at, 'userid' => Auth::user()->id]);
    }

    public function getSignin(){
        $users = DB::table('role')->select('id','value')->get();
        //return view('some-view')->with('users', $users);
        return view('user.signin',['user_data' => $users]);
    }
    public function postSignin(Request $request){
        $this->validate($request,[
            'email' => 'email|required',
            'password' => 'required|min:4',
            'role' => 'required'
        ]);
        if(Auth::attempt(['email' => $request->input('email'), 'password' => $request->input('password'), 'role' => $request->input('role'), 'status' => '1'])){
            $roles = DB::table('role')->select('id','value')->get();
			if(Auth::user()->status == "1"){
				$status = "Issued";
			}else{
				$status = "Revoke";
			}
			if(Auth::user()->role == 1){
				$incomplete = array();
				$count = 0;
				$leads = DB::table("leads")->select("*")->where([['user_id','=',Auth::user()->id],['document_status','=','Incomplete Documents']])->get()->toArray();
				if(count($leads) >= 1){
					foreach($leads as $lead){
						$incomplete[$count] = ['Name' => $lead->name, 'Age' => $lead->age, 'Nationality' => $lead->nationality, 'Passport_number' => $lead->passport_number, 'Document' => $lead->document_status];
						$count = $count+1;
					}
				}
			}else{
				$incomplete = "";
			}
            return redirect()->route('user.profile')->with(['role' => DB::table('role')->select('value')->where('id', Auth::user()->role)->get()->toArray(), 'username' => Auth::user()->username, 'email' => Auth::user()->email,'status' => $status, 'created_at' => Auth::user()->created_at, 'userid' => Auth::user()->id,'roles' => $roles, 'incomplete' => $incomplete]);
        }else{
            return redirect()->back();
        }
    }
    public function getProfile(){
        $roles = DB::table('role')->select('id','value')->get();
		if(Auth::user()->status == "1"){
			$status = "Issued";
		}else{
			$status = "Revoke";
		}
		if(Auth::user()->role == 1){
			$incomplete = array();
			$count = 0;
			$leads = DB::table("leads")->select("*")->where([['user_id','=',Auth::user()->id],['document_status','=','Incomplete Documents']])->get()->toArray();
			if(count($leads) >= 1){
				foreach($leads as $lead){
					$incomplete[$count] = ['Name' => $lead->name, 'Age' => $lead->age, 'Nationality' => $lead->nationality, 'Passport_number' => $lead->passport_number, 'Document' => $lead->document_status];
					$count = $count+1;
				}
			}
		}else{
			$incomplete = "";
		}
        return view('user.profile')->with(['role' => DB::table('role')->select('value')->where('id', Auth::user()->role)->get(), 'username' => Auth::user()->username, 'email' => Auth::user()->email,'status' => $status, 'created_at' => Auth::user()->created_at, 'userid' => Auth::user()->id,'roles' => $roles, 'incomplete' => $incomplete]);
    }

    public function getLogout(){
        Auth::logout();
        return redirect()->back();
    }

    public function get_marketer(){
        $marketers = DB::table("users")->select("id","username")->where([['role','=','2']])->get()->toArray();
        if(count($marketers) >= 1){
            echo '<label for="marketer">Marketer</label>';
            echo '<select name="marketer" id="role" class="form-control" required>';
                foreach($marketers as $data){
                    echo '<option value="'.$data->id.'">'.$data->username.'</option>';
                }
            echo '</select>';
        }else{
            echo "No Marketer Found.";
        }
    }

    public function all_agents(){
        $agents = DB::table("users")->select("*")->where([['m_id','=', Auth::user()->id]])->get()->toArray();
        //echo $agents['0']->id;
        if(count($agents) >= 1){
            echo '<table id="datatable" class="table table-bordered table-striped uk-table uk-table-hover uk-table-striped">';
            echo '<thead>';
            echo '<tr>';
                echo '<th>Username</th>';
                echo '<th>Email</th>';
                echo '<th>Leads</th>';
                echo '<th>Account Status</th>';
                echo '<th>Created</th>';
                echo '<th>Action</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach($agents as $agent){
                $leads = DB::table("leads")->select("*")->where([['user_id','=', $agent->id]])->get()->toArray();
                if($agent->status == "1"){
                    $status = "Issued";
                }else{
                    $status = "Revoked";
                }
                echo '<tr>';
                    echo "<td>".$agent->username."</td>";
                    echo "<td>".$agent->email."</td>";
                    echo "<td>".count($leads)."</td>";
                    echo "<td class='text-center'>".$status."</td>";
                    echo "<td>".$agent->created_at."</td>";
                    if($agent->status == "1"){
                        echo '<td class="text-center">
                            <select class="change_agent_status form-control" data-id="'.$agent->id.'">
                                <option value="">Change Status</option>
                                <option value="0">Revoke</option>
                            </select>
                        </td>';
                    }else{
                        echo '<td class="text-center">
                            <select class="change_agent_status form-control" data-id="'.$agent->id.'">
                                <option value="">Change Status</option>
                                <option value="1">Issue</option>
                            </select>
                        </td>';
                    }
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        }else{
            echo "No Agents Found.";
        }
    }

    public function change_agent_status(Request $request){
        $status = DB::table("users")->where([['id','=', $request->input('id')]])->update(["status" => $request->input('status')]);
        if($status == 1){
            echo "OK";
        }else{
            echo "Failed\nTry Again.";
        }
    }

    public function getCreate_new(Request $request){
        //return view('user.create_new')->with(['request' => ]);
        if(!empty($request->input('_token'))){
            $email_unique = DB::table('users')->select('id')->where([['email', '=', $request->input('email')]])->get()->toArray();
            if(count($email_unique) >= 1){
                echo "Email already Exist.";
            }else{
                $this->validate($request,[
                    'username' => 'required|min:4',
                    'email' => 'email|required|unique:users',
                    'password' => 'required|min:4',
                    'role' => 'required|min:1'
                ]);
                
                /*$user = new User([
                    'username' => $request->input('username'),
                    'email' => $request->input('email'),
                    'password' => bcrypt($request->input('password')),
                    'address' => $request->input('address'),
                    'm_id' => $request->input('marketer'),
                    'role' => $request->input('role')
                ]);*/
                $new_user = DB::table("users")->insertGetId([
                    'username' => $request->input('username'),
                    'email' => $request->input('email'),
                    'password' => bcrypt($request->input('password')),
                    'address' => $request->input('address'),
                    'm_id' => $request->input('marketer'),
                    'created_at' => date('y-m-d h:i:s'),
                    'role' => $request->input('role')
                ]);
                if($new_user >= 1){
                    echo 'OK';
                }else{
                    echo "Failed/nTry Again.";
                }
            }
        }else{
            echo "Token not found.";
        }
        /*foreach($request->input() as $k=>$v){
            echo $k.' = '.$v."\n";
        }*/
    }
    public function submit_new_lead(Request $request){
        $allowedfileExtension=['pdf','jpg','png','docx','doc','jpeg','xls','xlsx'];
        /*foreach($request->input() as $k=>$v){
            echo $k.' = '.$v."\n";
        }*/
        //var_dump($request->document['0']['0']->originalName);
        if(count($request->file('document')) >= 1){
            $id = DB::table('leads')->insertGetId(
                ['name' => $request->input('name'), 'age' => $request->input('age'), 'address' => $request->input('address'), 'nationality' => $request->input('nationality'), 'passport_number' => $request->input('passport_number'),'marital_status' => $request->input('marital_status'), 'user_id' => $request->input('user_id'), 'date' => date('y-m-d'), 'marketer_id' => Auth::user()->m_id]
            );
            if(!empty($id)){
                $errors = [];
                foreach($request->file('document') as $file){
                    $path = $file->path();
                    $extension = $file->getClientOriginalExtension();
                    $check = in_array($extension,$allowedfileExtension);
                    if($check){
                        $filename = $file->store('public/document');
                        DB::table('documents')->insertGetId(['path' => $filename, 'lead_id' => $id]);
                    }else{
                        $errors[] = $file->getClientOriginalName()." is Invalid file type";
                    }
                }
                if(count($errors) >= 1){
                    foreach($errors as $error){
                        echo $error."\n";
                    }
                    echo "Lead uploaded successfully";
                }else{
                    echo "OK";
                }
            }else{
                echo "Failed\nTry again";
            }
        }
    }

    public function get_all_leads(Request $request){
        if($request->input('id')){
            $leads = DB::table('leads')->select('*')->where([['user_id','=',$request->input('id')]])->orderBy('date', 'desc')->get()->toArray();
        }else{
            $leads = DB::table('leads')->select('*')->orderBy('date', 'desc')->get()->toArray();
        }
        //var_dump($leads);
        if(count($leads) >= 1){
            echo '<table id="datatable" class="table table-bordered table-striped uk-table uk-table-hover uk-table-striped">';
            echo '<thead>';
            echo '<tr>';
                echo '<th>Name</th>';
                echo '<th>Nationality</th>';
                echo '<th>Passport</th>';
                echo '<th>Married</th>';
                echo '<th>Date</th>';
                echo '<th>Application</th>';
				echo '<th>Document</th>';
                echo '<th>View</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach($leads as $lead){
                if($lead->status == "Close"){
                    $status = "In Repository";
                }else{
                    $status = $lead->status;
                }
                echo '<tr>';
                    echo '<td>'.$lead->name.'</td>';
                    echo '<td>'.$lead->nationality.'</td>';
                    echo '<td>'.$lead->passport_number.'</td>';
                    echo '<td>'.$lead->marital_status.'</td>';
                    echo '<td>'.$lead->date.'</td>';
                    echo '<td>'.$status.'</td>';
					if(($lead->document_status == "") || ($lead->document_status == "Pending Documents") || ($lead->document_status == "Incomplete Documents")){
						if($lead->document_status == ""){
							echo '<td style="color:red;font-weight:bold;">No Action</td>';
						}if($lead->document_status == "Pending Documents"){
							echo '<td style="color:red;font-weight:bold;">Pending Documents</td>';
						}else{
							echo '<td style="color:red;font-weight:bold;">Incomplete Documents</td>';
						}
					}else{
						echo '<td style="color:green;font-weight:bold;">Complete Documents</td>';
					}
                    echo '<td class="text-center">
                        <button data-id="'.$lead->id.'" class="view_lead lead_info btn btn-info btn-sm text-white"><i class="fas fa-eye"></i> Option</button>
                        </td>';
                echo '</tr>';
            }
                echo '</tbody>';
            echo '</table>';
        }else{
            echo "No Leads Found.";
        }
    }
    public function get_all_leads_marketer(Request $request){
        if($request->input('id') !== null){
            $leads = DB::table('leads')->select('*')->where([['user_id','=',$request->input('id')]])->orderBy('date', 'desc')->get()->toArray();
            if(count($leads) >= 1){
                echo '<table id="datatable" class="table table-bordered table-striped uk-table uk-table-hover uk-table-striped">';
                echo '<thead>';
                echo '<tr>';
                    echo '<th>Name</th>';
                    echo '<th>Nationality</th>';
                    echo '<th>Passport</th>';
                    echo '<th>Marriage</th>';
                    echo '<th>Application</th>';
					echo '<th>Document</th>';
                    echo '<th>Date</th>';
                    echo '<th>Action</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach($leads as $lead){
                    echo '<tr>';
                        echo '<td>'.$lead->name.'</td>';
                        echo '<td>'.$lead->nationality.'</td>';
                        echo '<td>'.$lead->passport_number.'</td>';
                        echo '<td>'.$lead->marital_status.'</td>';
                        if($lead->status == "Close"){
                            echo '<td>In Repository</td>';
                        }else{
                            echo '<td>'.$lead->status.'</td>';
                        }
						if(($lead->document_status == "No Action") || ($lead->document_status == "Pending Documents") || ($lead->document_status == "Incomplete Documents")){
							if($lead->document_status == "No Action"){
								echo '<td style="color:red;font-weight:bold;">No Action</td>';
							}if($lead->document_status == "Pending Documents"){
								echo '<td style="color:red;font-weight:bold;">Pending Documents</td>';
							}if($lead->document_status == "Incomplete Documents"){
								echo '<td style="color:red;font-weight:bold;">Incomplete Documents</td>';
							}
						}else{
							echo '<td style="color:green;font-weight:bold;">Complete Documents</td>';
						}
                        echo '<td>'.$lead->date.'</td>';
                        echo '<td class="text-center">
                            <button data-id="'.$lead->id.'" class="edit_lead lead_info btn btn-primary btn-sm"><i class="fas fa-pen"></i> Edit</button>
                            </td>';
                    echo '</tr>';
                }
                    echo '</tbody>';
                echo '</table>';
            }else{
                echo "No Leads Found.";
            }
        }else{
            $users = DB::table("users")->select("id")->where([['m_id','=', Auth::user()->id]])->get();
            echo '<table id="datatable" class="table table-bordered table-striped uk-table uk-table-hover uk-table-striped">';
            echo '<thead>';
            echo '<tr>';
                echo '<th>Name</th>';
                echo '<th>Age</th>';
                echo '<th>Nationality</th>';
                echo '<th>Passport</th>';
                echo '<th>Marriage</th>';
                echo '<th>Application</th>';
				echo '<th>Document</th>';
                echo '<th>Date</th>';
                echo '<th>Action</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach($users as $user){
                //echo $user->id;
                $leads = DB::table('leads')->select('*')->where([['user_id','=',$user->id]])->orderBy('date', 'desc')->get();
                if(count($leads) >= 1){
                    foreach($leads as $lead){
                        echo '<tr>';
                            echo '<td>'.$lead->name.'</td>';
                            echo '<td>'.$lead->age.'</td>';
                            echo '<td>'.$lead->nationality.'</td>';
                            echo '<td>'.$lead->passport_number.'</td>';
                            echo '<td>'.$lead->marital_status.'</td>';
                            if($lead->status == "Close"){
								if($lead->repository == 0){
									echo '<td><a data-id="'.$lead->id.'" href="javascript:void(0)" class="btn btn-primary btn-xs save_in_repository">Save in repository</a></td>';
								}else{
									echo '<td style="color:green;font-weight:bold;">In Repository</td>';
								}
                            }else{
                                echo '<td>'.$lead->status.'</td>';
                            }
							if(($lead->document_status == "No Action") || ($lead->document_status == "Pending Documents") || ($lead->document_status == "Incomplete Documents")){
								if($lead->document_status == "No Action"){
									echo '<td style="color:red;font-weight:bold;">No Action</td>';
								}if($lead->document_status == "Pending Documents"){
									echo '<td style="color:red;font-weight:bold;">Pending Documents</td>';
								}if($lead->document_status == "Incomplete Documents"){
									echo '<td style="color:red;font-weight:bold;">Incomplete Documents</td>';
								}
							}else{
								echo '<td style="color:green;font-weight:bold;">Complete Documents</td>';
							}
                            echo '<td>'.$lead->date.'</td>';
                            echo '<td class="text-center">
                                <button data-id="'.$lead->id.'" class="edit_lead lead_info btn btn-primary btn-sm"><i class="fas fa-pen"></i> Edit</button>
                                </td>';
                        echo '</tr>';
                    }
                }
            }
            echo '</tbody>';
            echo '</table>';
        }
       // var_dump($leads);
    }

    public function get_lead_info(Request $request){
        $data = DB::table('leads')->select('*')->where([['id','=',$request->input('id')]])->get()->toArray();
        $marketer = DB::table('users')->select('*')->where([['id','=',$data['0']->user_id]])->get()->toArray();
        if(count($data) >= 1){
            ?>
            <a href="javascript:void(0)" class="float-right btn close_lead_info"><i class="fas fa-times"></i></a>
            <div class="row">
                <div class="col-md-12 panel-heading"><h2>Lead Info</h2></div>
                <div class="col-md-3 col-lg-3">
                    <ul class="list-group">
                        <?php
                            if(Auth::user()->role == 1){
                                ?>
                                <li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-personal" class="custom-tabs active"><i class="fas fa-credit-card"></i> Personal</a></li>
                                <li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-document" class="custom-tabs"><i class="fas fa-file"></i> Document</a></li>
								<li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-notes" class="custom-tabs"><i class="fas fa-code"></i> Notes</a></li>
                                <li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-messaging" class="custom-tabs"><i class="fas fa-comment"></i> Chat</a></li>
                                <?php
                            }else{
                                ?>
                                <li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-personal" class="custom-tabs active"><i class="fas fa-credit-card"></i> Personal</a></li>
                                <li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-agent" class="custom-tabs"><i class="fas fa-user"></i> Agent</a></li>
                                <li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-document" class="custom-tabs"><i class="fas fa-file"></i> Document</a></li>
								<li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-notes" class="custom-tabs"><i class="fas fa-code"></i> Notes</a></li>
                                <li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-messaging" class="custom-tabs"><i class="fas fa-comment"></i> Chat with agent</a></li>
                                <?php
                            }
                        ?>
                    </ul>
                </div>
                <div class="col-md-9 col-lg-9">
                    <div class="row">
                    <div class="tabs_content active" id="tab-personal">
                        <div class="col-md-12 col-lg-12">
                            <h2>Personal Information</h2><hr>
                            <p><strong>Name : <?php echo $data['0']->name; ?></strong></p>
                            <p><strong>Age : <?php echo $data['0']->age; ?></strong></p>
                            <p><strong>Nationality : <?php echo $data['0']->nationality; ?></strong></p>
                            <p><strong>Passport Number : <?php echo $data['0']->passport_number; ?></strong></p>
                            <p><strong>Marital Status : <?php echo $data['0']->marital_status; ?></strong></p>
							<hr>
							<?php
								if(Auth::user()->role == 2){
							?>
                            <div class="row">
                                <div class="col-md-12 col-lg-12">
                                    <p><strong>Application Status</strong></p>
                                    <p>
                                        <select name="lead_status" class="form-control change_lead_status">
                                            <option value="">Select Application Status</option>
                                            <option value="Incomplete" <?php if($data['0']->status == "Incomplete"){echo 'selected';} ?>>Incomplete</option>
                                            <option value="Rejected" <?php if($data['0']->status == "Rejected"){echo 'selected';} ?>>Rejected</option>
                                            <option value="Approved" <?php if($data['0']->status == "Approved"){echo 'selected';} ?>>Approved</option>
                                            <option value="Complete" <?php if($data['0']->status == "Complete"){echo 'selected';} ?>>Complete</option>
                                            <option value="Close" <?php if($data['0']->status == "Close"){echo 'selected';} ?>>Close</option>
                                        </select>  
                                    </p>
                                    <p>
                                        <button class="btn btn-success update_application_status" data-id="<?php echo $data['0']->id ?>">Update</button>
                                    </p>
                                </div>
                            </div>
							<?php
								}
							?>
                        </div>
                    </div>
                    <div class="tabs_content" id="tab-agent">
                        <div class="col-md-12 col-lg-12">
                            <h2>Agent Information</h2><hr>
                            <?php
                            $agent = DB::table("users")->select('*')->where([['id','=',$data['0']->user_id]])->get()->toArray();
                            //var_dump($agent);
                            ?>
                            <p><strong>Username : <?php echo $agent['0']->username; ?></strong></p>
                            <p><strong>Email : <?php echo $agent['0']->email; ?></strong></p>
                            <p><strong>Account Created : <?php echo $agent['0']->created_at; ?></strong></p>
                            <p><strong>Account Status : <?php if($agent['0']->status == '1'){echo 'Issued';}else{echo 'Revoked';} ?></strong></p>
                        </div>
                    </div>
                    <div class="tabs_content" id="tab-document">
                        <div class="col-md-12 col-lg-12">
                            <h2>Documents
							<?php
								if(Auth::user()->role == 1){
							?>
								<a href="javascript:void(0)" class="upload_new_document btn btn-info float-right text-white"><i class="fas fa-upload"></i> Upload Document</a><?php } ?></h2><hr>
                            <div class="new_document alert alert-success animated">
                                <form id="new_document" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="document">Document</label>
                                        <input type="file" name="document[]" multiple id="document" class="form-control" />
                                        <input type="hidden" name="lead_id" value="<?php echo $data['0']->id; ?>" />
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Upload</button>
                                    </div>
                                </form>
                            </div>
                            <?php
                                $document = DB::table('documents')->select('*')->where([['lead_id','=',$data['0']->id]])->get()->toArray();
                                $image_extension = ['jpg','jpeg','png'];
                                $document_extension = ['doc','docx','pdf','xls','xlsx'];
                                foreach($document as $file){
                                    //echo storage_path($file->path);  // Return path like: laravel_app\storage
									$path = "../../laravel_app/".str_replace('public','storage/app/public/',$file->path);
                                    $ext = explode('.', $file->path);
                                    if(in_array($ext['1'],$image_extension)){
                                        ?>
                                            <a href="<?php echo $path; ?>" data-lightbox="image-1" class="lightbox-tag"><img class="thumb" src="<?php echo $path; ?>" alt="" /></a>
                                        <?php
                                    }else{
                                        ?>
											<a href="<?php echo asset($path); ?>" style="float:left;width:250px;margin-right:10px">
												<div class="card">
													<div class="card-body text-center">
														<div class="card-image">
															<i class="fas fa-file-alt fa-5x"></i>
														</div>
														<hr>
														<strong class="card-title"><?php echo $ext[1]." file download" ?></strong>
													</div>
												</div>
											</a>
                                            <!--<embed src="<?php echo asset($path); ?>" type='<?php echo $ext[1]; ?>' />-->
                                        <?php
                                    }
                                }
                            ?>
                        </div>
						<?php
							if(Auth::user()->role == 2){
						?>
						<div style="float:left;width:100%;height:auto;">
							<br /><br />
						<hr/>
							<p><strong>Document Status</strong></p>
							<p>
								<select name="document_status" class="form-control change_document_status">
									<option value="">Select Document Status</option>
									<option value="Incomplete Documents" <?php if($data['0']->document_status == "Incomplete Documents"){echo 'selected';} ?>>Incomplete Documents</option>
									<option value="Pending Documents" <?php if($data['0']->document_status == "Pending Documents"){echo 'selected';} ?>>Pending Documents</option>
									<option value="Complete Documents" <?php if($data['0']->document_status == "Complete Documents"){echo 'selected';} ?>>Complete Documents</option>
								</select>  
							</p>
							<p>
								<button class="btn btn-success update_document_status" data-id="<?php echo $data['0']->id ?>">Update</button>
							</p>
						</div>
						<?php
							}
						?>
                    </div>
					<div class="tabs_content" id="tab-notes">
                        <div class="col-md-12 col-lg-12">
							<h2>Notes</h2><hr>
							<form id="add_notes_for_lead">
								<div class="form-group">
									<label for="notes">Notes</label>
									<textarea class="form-control" name="notes" id="notes" style="resize:none;width:100%;height:250px;">
									<?php
									$notes = DB::table("notes")->select("*")->where([['user_id','=',Auth::user()->id]])->get()->toArray();
									if(count($notes) >= 1){
										foreach($notes as $note){
											echo $note->content;
										}
									}
									?>
									</textarea>
								</div>
								<div class="form-group">
									<input type="hidden" name="lead_id" value="<?php echo $data['0']->id; ?>">
									<input type="submit" name="submit" class="btn btn-success" />
								</div>
							</form>
						</div>
					</div>
                    <div class="tabs_content" id="tab-messaging">
                        <div class="col-md-12 col-lg-12">
                            <h2>Messaging</h2><hr>
                            <?php
                                $agent = DB::table("users")->select('*')->where([['id','=',$data['0']->user_id]])->get()->toArray();
                            ?>
                            <div class="row">
                                <div class="col-md-12 col-lg-12">
                                    <div class="message_box mb-3"><div class="all_messages"></div></div>
                                    <form class="messaging">
                                        <input type="hidden" name="lead_id" value="<?php echo $data['0']->id ?>" />
                                        <input type="hidden" name="msg_from" value="<?php echo $marketer['0']->m_id ?>" />
                                        <input type="hidden" name="msg_to" value="<?php echo $data['0']->user_id ?>" />
                                        <div class="input-group mb-3">
                                            <textarea name="message" id="message" required class="form-control" style="resize:none;" placeholder="Write Message"></textarea>
                                            <div class="input-group-append">
                                                <button type="submit" class="send btn btn-success"><i class="fas fa-comments"></i> Send</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            
        }else{
            echo "No Lead Found.";
        }
    }

    public function change_application_status(Request $request){
        $status = DB::table("leads")->where([['id','=', $request->input('id')]])->update(["status" => $request->input('status')]);
        if($status == 1){
            echo "OK";
        }else{
            echo "Failed\nTry Again.";
        }
    }
	
	public function change_document_status(Request $request){
        $status = DB::table("leads")->where([['id','=', $request->input('id')]])->update(["document_status" => $request->input('status')]);
        if($status == 1){
            echo "OK";
        }else{
            echo "Failed\nTry Again.";
        }
    }
	
	public function in_repository(Request $request){
        //$documents = DB::table("leads")->where([['id','=', $request->input('id')]])->update(["repository" => 1]);
		$files = DB::table("documents")->select("*")->where([['lead_id','=',$request->input("id")]])->get()->toArray();
		$folder_name = $request->input("id");
		$folder_new = Storage::makeDirectory($folder_name);
		foreach($files as $file){
			echo $file->path;
			$path = str_replace('public',$folder_name,$file->path);
			Storage::copy($file->path, $path);
			//$zip->addFile($file->path);
			$zip = new ZipArchive;
			if ($zip->open('test.zip') === TRUE) {
				$filename = explode('/',$file->path);
				$zip->addFile($file->path, $filename[2]);
				$zip->close();
			}
		}
		$marketer_notes = DB::table("notes")->select("*")->where([['lead_id','=',$request->input("id")]])->get()->toArray();
		$data = $marketer_notes[0]->description;
		Storage::put( $folder_name.'/notes.txt', $data );
		$documents = DB::table("leads")->where([['id','=', $request->input('id')]])->update(["repository" => 1]);
		if($documents >= 1){
			echo "OK";
		}else{
			echo "Failed\nTry again.";
		}
    }
	
	
	public function add_notes(Request $request){
	$exist = DB::table("notes")->select("*")->where([['user_id','=',Auth::user()->id],['lead_id','=',$request->input("lead_id")]])->get()->toArray();
	if(count($exist) >= 1){
		$documents = DB::table("notes")->where([['user_id','=',Auth::user()->id],['lead_id','=',$request->input("lead_id")]])->update(['description' => $request->input("notes")]);
		if($documents >= 1){
			echo "OK";
		}else{
			echo "Failed\nTry again.";
		}
	}else{
	$documents = DB::table("notes")->insertGetId([
		'lead_id' => $request->input("lead_id"), 'user_id' => Auth::user()->id, 'description' => $request->input("notes"), "status" => "1"
	]);
		if($documents >= 1){
			echo "OK";
		}else{
			echo "Failed\nTry again.";
		}
		}
	}

    public function chat_history(Request $request){
		if(Auth::check()){
			$logged_id = Auth::user()->id;
			$lead_id = $request->input('lead_id');
			$messages = DB::table("messages")->select('*')->where([['id','=',$lead_id]])->get()->toArray();
			foreach($messages as $message){
				if($message->msg_from == $logged_id){
					echo '<span class="me">';
						echo '<p class="from">Me:</p><hr>';
						echo '<p>'.$message->message.'</p>';
					echo '</span>';
				}else{
					$user = DB::tabel("users")->select('*')->where([['id','=',$message->msg_to]])->get()->toArray();
					echo '<span class="not-me">';
						echo '<p class="to">'.$user['0']->username.':</p><hr>';
						echo '<p>'.$message->message.'</p>';
					echo '</span>';
				}
			}
		}else{
			$lead_id = $request->input('lead_id');
			$messages = DB::table("messages")->select('*')->where([['id','=',$lead_id]])->get()->toArray();
			foreach($messages as $message){
				$user = DB::tabel("users")->select('*')->where([['id','=',$message->msg_to]])->get()->toArray();
				echo '<span class="not-me">';
                    echo '<p class="to">'.$user['0']->username.':</p><hr>';
                    echo '<p>'.$message->message.'</p>';
                echo '</span>';
				/*if($message->msg_from == $logged_id){
					echo '<span class="me">';
						echo '<p class="from">Me:</p><hr>';
						echo '<p>'.$message->message.'</p>';
					echo '</span>';
				}else{
					$user = DB::tabel("users")->select('*')->where([['id','=',$message->msg_to]])->get()->toArray();
					echo '<span class="not-me">';
						echo '<p class="to">Testing:</p><hr>';
						echo '<p>'.$message->message.'</p>';
					echo '</span>';
				}*/
			}
		}
    }

    public function lead_message(Request $request){
		if(Auth::check()){
			$logged_id = Auth::user()->id;
			$messages = DB::table("messages")->select('*')->where('lead_id',$request->input('lead_id'))->get()->toArray();
			foreach($messages as $message){
				DB::table("messages")->where([['lead_id','=',$message->lead_id]])->update(['from_status' => '1']);
				$user = DB::table("users")->select('username')->where([['id','=',$message->msg_to]])->get();
				if($message->msg_from == $logged_id){
					echo '<span class="me">';
						echo '<p class="from">Me:</p><hr>';
						echo '<p>'.$message->message.'</p>';
					echo '</span>';
				}else{
					echo '<span class="not-me">';
						echo '<p class="to">'.$user['0']->username.':</p><hr>';
						echo '<p>'.$message->message.'</p>';
					echo '</span>';
				}
			}
		}else{
			//echo "Working";
			$messages = DB::table("messages")->select('*')->where('lead_id',$request->input('lead_id'))->get()->toArray();
			foreach($messages as $message){
				$user = DB::tabel("users")->select('*')->where([['id','=',$message->msg_to]])->get()->toArray();
				echo '<span class="not-me">';
                    echo '<p class="to">'.$user['0']->username.':</p><hr>';
                    echo '<p>'.$message->message.'</p>';
                echo '</span>';
			}
		}
    }


    public function new_chat_message(Request $request){
        $logged_id = Auth::user()->id;
        $msg = DB::table("messages")->insertGetId([
            'message' => $request->input('message'), 'msg_from' => $logged_id, 'msg_to' => $request->input('msg_to'), 'to_status' => '0', 'from_status' => '0', 'lead_id' => $request->input('lead_id')
        ]);
        /*if($msg >= 1){
            echo "OK";
        }*/
    }

    public function upload_new_document(Request $request){
        //echo $request->input('lead_id');
        if(count($request->file('document')) >= 1){
            $allowedfileExtension=['pdf','jpg','png','docx','doc','jpeg','xls','xlsx'];
            $id = $request->input('lead_id');
            if(!empty($id)){
                $errors = [];
                foreach($request->file('document') as $file){
                    $path = $file->path();
                    $extension = $file->getClientOriginalExtension();
                    $check = in_array($extension,$allowedfileExtension);
                    if($check){
                        $filename = $file->store('public/document');
                        DB::table('documents')->insertGetId(['path' => $filename, 'lead_id' => $id]);
                    }else{
                        $errors[] = $file->getClientOriginalName()." is Invalid file type";
                    }
                }
                if(count($errors) >= 1){
                    foreach($errors as $error){
                        echo $error."\n";
                    }
                }else{
                    echo "OK";
                }
            }else{
                echo "Failed\nTry again";
            }
        }
        //echo count($request->file('document'));
    }

    public function get_agent_lead_message(Request $request){
        $logged_id = Auth::user()->id;
        $leads = DB::table("leads")->select("id")->where([['user_id','=',$logged_id]])->get();
        //echo $leads['0']->id;
        ?>
        <div class="message_box">
            <div class="all_messages">
        <?php
        foreach($leads as $lead){
            $messages = DB::table("messages")->select('*')->where('lead_id',$lead->id)->get()->toArray();
            foreach($messages as $message){
                DB::table("messages")->where([['lead_id','=',$message->lead_id]])->update(['from_status' => '1']);
                $user = DB::table("users")->select('username')->where([['id','=',$message->msg_from]])->get();
                if($message->msg_from == $logged_id){
                    echo '<span class="me">';
                        echo '<p class="from">Me:</p><hr>';
                        echo '<p>'.$message->message.'</p>';
                    echo '</span>';
                }else{
                    echo '<span class="not-me">';
                        echo '<p class="to">'.$user['0']->username.':</p><hr>';
                        echo '<p>'.$message->message.'</p>';
                    echo '</span>';
                }
            }
        }
        ?>
            </div>
        </div>
        <?php
    }

}
