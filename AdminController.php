<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use Session;

class AdminController extends Controller
{
    public function getadmin(){
        return view('admin.admin');
    }
    public function postadmin_login(Request $request){
        $this->validate($request,[
            'username' => 'required',
            'password' => 'required|min:4'
        ]);
        $admin_login = DB::table('admin_login')->select('id')->where([['username', '=', MD5($request->input('username'))],['password', '=', MD5($request->input('password'))]])->get()->toArray();
        if(count($admin_login) > 0){
            $login_access = json_decode($admin_login[0]->id, true);
            //return redirect()->route('admin.dashboard',['data' => $admin_login]);
            if($login_access == 1){
                Session::put('admin_logged_in','OK');
                $users = DB::table('role')->select('id','value')->get()->toArray();
                //return view('admin.dashboard',['roles' => $users]);
                return redirect()->route('admin.dashboard',['roles' => $users]);
            }else{
                $error = array("error"=>"Access Denied","error message"=>"Invalid Username and Password");
                return view('admin.admin')->with(['errors' => $error]);
            }
        }else{
            $error = array("error"=>"Access Denied","error message"=>"Invalid Username and Password");
            return view('admin.admin')->with(['errors' => $error]);
        }
        //return view('user.admin',['username' => $request->input('username'), 'password' => $request->input('password')]);
    }
    public function getDashboard(){
        if(!Session::has('admin_logged_in')){
            Session::flush();
            return redirect()->route('admin.admin');
        }else{
            $users = DB::table('role')->select('id','value')->get()->toArray();
            return view('admin.dashboard',['roles' => $users]);
        }
        //dd(session()->get('admin_logged_in'));
        //return view('admin.dashboard');
    }

    public function get_leads(Request $request){
        if(!Session::has('admin_logged_in')){
            Session::flush();
            return redirect()->route('admin.admin');
        }else{
            $data = DB::table('leads')->select('*')->orderBy('date', 'desc')->get()->toArray();
            if(count($data) >= 1){
                echo '<table id="datatable" class="table table-bordered table-striped uk-table uk-table-hover uk-table-striped">';
                    echo '<thead>';
                    echo '<tr>';
						echo '<th>Name</th>';
						echo '<th>Age</th>';
						echo '<th>Nationality</th>';
						echo '<th>Passport</th>';
						echo '<th>Marital Status</th>';
						echo '<th>Date</th>';
						echo '<th>Status</th>';
						echo '<th>Action</th>';
					echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                foreach($data as $lead){
					if($lead->status == "Close"){
						$status = "In Repository";
					}else{
						$status = $lead->status;
					}
                    $user = DB::table('users')->select('*')->where([['id' ,'=', $lead->user_id]])->get()->toArray();
                    echo '<tr>';
                        echo '<td>'.$lead->name.'</td>';
                    echo '<td>'.$lead->age.'</td>';
                    echo '<td>'.$lead->nationality.'</td>';
                    echo '<td>'.$lead->passport_number.'</td>';
                    echo '<td>'.$lead->marital_status.'</td>';
                    echo '<td>'.$lead->date.'</td>';
                    echo '<td>'.$status.'</td>';
                    echo '<td class="text-center">';
                        echo '<button data-id="'.$lead->id.'" class="lead_info btn btn-primary btn-sm">View Info</button></td>';
                    echo '</tr>';
                }
                    echo '</tbody>';
                echo '</table>';
            }else{
                echo "No Leads found.";
            }
        }
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
						<li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-personal" class="custom-tabs active"><i class="fas fa-credit-card"></i> Personal</a></li>
						<li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-agent" class="custom-tabs"><i class="fas fa-user"></i> Agent</a></li>
						<li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-document" class="custom-tabs"><i class="fas fa-file"></i> Document</a></li>
						<li class="list-group-item"><a href="javascript:void(0)" data-id="#tab-messaging" class="custom-tabs"><i class="fas fa-comment"></i> Chats</a></li>
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
                            <div class="row">
                                <div class="col-md-12 col-lg-12">
                                    <p><strong>Application Status</strong></p>
                                    <p>
                                        <select name="lead_status" class="form-control change_lead_status">
                                            <option value="">Select Application Status</option>
                                            <option value="Incomplete" <?php if($data['0']->status == "Incomplete"){echo 'selected';} ?>>Incomplete</option>
                                            <option value="Refuse" <?php if($data['0']->status == "Refuse"){echo 'selected';} ?>>Refuse</option>
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
                            <h2>Documents<a href="javascript:void(0)" class="upload_new_document btn btn-info float-right text-white"><i class="fas fa-upload"></i> Upload Document</a></h2><hr>
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
                                $document_extension = ['doc','docx'];
                                foreach($document as $file){
                                    //echo storage_path($file->path);  // Return path like: laravel_app\storage
                                    $ext = explode('.', $file->path);
                                    if(in_array($ext['1'],$image_extension)){
                                        $path = "../../laravel_app/".str_replace('public','storage/app/public',$file->path)
                                        ?>
                                            <a href="<?php echo $path; ?>" data-lightbox="image-1" class="lightbox-tag"><img class="thumb" src="<?php echo $path; ?>" alt="" /></a>
                                        <?php
                                    }else{
                                        ?>
                                            <embed src="<?php echo asset('storage/'.$file->path); ?>" type='<?php echo $ext; ?>' />
                                        <?php
                                    }
                                }
                            ?>
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
                                    <!--<form class="messaging">
                                        <input type="hidden" name="lead_id" value="<?php echo $data['0']->id ?>" />
                                        <input type="hidden" name="msg_from" value="<?php echo $marketer['0']->m_id ?>" />
                                        <input type="hidden" name="msg_to" value="<?php echo $data['0']->user_id ?>" />
                                        <div class="input-group mb-3">
                                            <textarea name="message" id="message" class="form-control" style="resize:none;" placeholder="Write Message"></textarea>
                                            <div class="input-group-append">
                                                <button type="submit" class="send btn btn-success"><i class="fas fa-comments"></i> Send</button>
                                            </div>
                                        </div>
                                    </form>-->
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

    public function get_agents(Request $request){
        if(!Session::has('admin_logged_in')){
            Session::flush();
            return redirect()->route('admin.admin');
        }else{
            $role = DB::table('role')->select('*')->where([['value','=','agent']])->get()->toArray();
            //var_dump($role['0']->id);
            if(count($role) >= 1){
                $data = DB::table('users')->select('*')->where([['role','=',$role['0']->id]])->get()->toArray();
                if(count($data) >= 1){
                    echo '<table id="datatable" class="table table-bordered table-striped uk-table uk-table-hover uk-table-striped">';
                        echo '<thead>';
                        echo '<tr>';
                            echo '<th>Username</th>';
                            echo '<th>Email</th>';
                            echo '<th>Created</th>';
                            echo '<th>Leads</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                    foreach($data as $user){
                        $leads = DB::table('leads')->select('*')->where([['user_id' ,'=', $user->id]])->get()->toArray();
                        echo '<tr>';
                            echo '<td>'.$user->username.'</td>';
                            echo '<td>'.$user->email.'</td>';
                            echo '<td>'.$user->created_at.'</td>';
                            echo '<td>'.count($leads).'</td>';
                        echo '</tr>';
                    }
                        echo '</tbody>';
                    echo '</table>';
                }
            }
        }
    }

    public function get_marketers(Request $request){
        if(!Session::has('admin_logged_in')){
            Session::flush();
            return redirect()->route('admin.admin');
        }else{
            $role = DB::table('role')->select('*')->where([['value','=','marketer']])->get()->toArray();
            //var_dump($role['0']->id);
            if(count($role) >= 1){
                $data = DB::table('users')->select('*')->where([['role','=',$role['0']->id]])->get()->toArray();
                if(count($data) >= 1){
                    echo '<table id="datatable" class="table table-bordered table-striped uk-table uk-table-hover uk-table-striped">';
                        echo '<thead>';
                        echo '<tr>';
                            echo '<th>Username</th>';
                            echo '<th>Email</th>';
                            echo '<th>Created</th>';
                            echo '<th>Agents</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                    foreach($data as $user){
                        $leads = DB::table('users')->select('*')->where([['m_id' ,'=', $user->id]])->get()->toArray();
                        echo '<tr>';
                            echo '<td>'.$user->username.'</td>';
                            echo '<td>'.$user->email.'</td>';
                            echo '<td>'.$user->created_at.'</td>';
                            echo '<td>'.count($leads).'</td>';
                        echo '</tr>';
                    }
                        echo '</tbody>';
                    echo '</table>';
                }
            }
        }
    }

    public function adminLogout(){
        Session::flush();
        return redirect()->route('admin.admin');
    }
}
