<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Input;
use App\Item;
use DB;
use Excel;
use App\User;

class MaatwebsiteDemoController extends Controller
{
    public function importExport()
	{
		return view('importExport');
	}
	public function agents($type, $startDate, $stopDate)
	{
		$get_data = DB::table('users')->select('*')->whereBetween('created_at', array($startDate, $stopDate))->where('role',1)->get()->toArray();
		if(count($get_data) <= 0){
			$get_data = "No Data Found.";
		}
		$data = json_decode( json_encode($get_data), true);
		$file_name = "Report_".date('d-m-y').'_'.MD5(date('r'));
		return Excel::create($file_name, function($excel) use ($data) {
			$excel->sheet('mySheet', function($sheet) use ($data)
	        {
				$sheet->fromArray($data);
	        });
		})->download($type);
	}
	public function leads($type, $startDate, $stopDate)
	{
		$data = DB::table('leads')->select('*')->whereBetween('date', array($startDate, $stopDate))->whereIn('status',array('Open','Incomplete','Complete'))->get()->toArray();
		if(count($data) <= 0){
			$data = "No Data Found.";
		}
		$data = json_decode( json_encode($data), true);
		$file_name = "Report_".date('d-m-y').'_'.MD5(date('r'));
		return Excel::create($file_name, function($excel) use ($data) {
			$excel->sheet('mySheet', function($sheet) use ($data)
	        {
				$sheet->fromArray($data);
	        });
		})->download($type);
	}
	public function leadsacceptreject($type, $startDate, $stopDate)
	{
        //$data = Item::get()->toArray();
        //$data = User::all()->toArray();
		$data = DB::table('leads')->select('*')->whereBetween('date', array($startDate, $stopDate))->whereIn('status',array('Approved','Rejected'))->get()->toArray();
		if(count($data) <= 0){
			$data = "No Data Found.";
		}
		$data = json_decode( json_encode($data), true);
		$file_name = "Report_".date('d-m-y').'_'.MD5(date('r'));
		return Excel::create($file_name, function($excel) use ($data) {
			$excel->sheet('mySheet', function($sheet) use ($data)
	        {
				$sheet->fromArray($data);
	        });
		})->download($type);
	}
	public function leads_per_agent($type, $startDate, $stopDate)
	{
        //$data = Item::get()->toArray();
		$array = array('Username','Email','Leads');
		$users = DB::table("users")->select('*')->whereBetween('created_at', array($startDate, $stopDate))->where([['role','=','1']])->get();
		$count = 0;
		foreach($users as $user){
			//$data[] = $user->id;
			$array[$count] = ['Username' => $user->username, 'Email' => $user->email, 'Leads' => count(DB::table('leads')->select('*')->where([['user_id','=',$user->id]])->get())];
			$count = $count+1;
		}
		//$data = $leads;
		$data = $array;
		$data = json_decode( json_encode($data), true);
		$file_name = "Report_".date('d-m-y').'_'.MD5(date('r'));
		return Excel::create($file_name, function($excel) use ($data) {
			$excel->sheet('mySheet', function($sheet) use ($data)
	        {
				$sheet->fromArray($data);
	        });
		})->download($type);
	}
	public function leads_closing_per_marketer($type, $startDate, $stopDate)
	{
        //$data = Item::get()->toArray();
		//$data = User::all()->toArray();
		$users = DB::table("users")->select('id')->where([['role','=','1']])->get();
		foreach($users as $user){
			$data = DB::table('leads')->select('*')->whereBetween('date', array($startDate, $stopDate))->where([['status','=','Close']])->get()->toArray();
			$data = json_decode( json_encode($data), true);
		}

		if(count($data) <= 0){
			$data = "No Data Found.";
		}
		$file_name = "Report_".date('d-m-y').'_'.MD5(date('r'));
		return Excel::create($file_name, function($excel) use ($data) {
			$excel->sheet('mySheet', function($sheet) use ($data)
	        {
				$sheet->fromArray($data);
	        });
		})->download($type);
	}
	public function importExcel()
	{
		if(Input::hasFile('import_file')){
			$path = Input::file('import_file')->getRealPath();
			$data = Excel::load($path, function($reader) {
			})->get();
			if(!empty($data) && $data->count()){
				foreach ($data as $key => $value) {
					$insert[] = ['title' => $value->title, 'description' => $value->description];
				}
				if(!empty($insert)){
					DB::table('items')->insert($insert);
					dd('Insert Record successfully.');
				}
			}
		}
		return back();
	}
}
