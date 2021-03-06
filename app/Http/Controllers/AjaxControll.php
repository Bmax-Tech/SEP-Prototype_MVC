<?php

namespace App\Http\Controllers;

use DB;
use App\Chat_data;
use App\Comments;
use App\Doctors;
use App\Images;
use App\Patients;
use App\User;
use App\NewsLetterSubscriber;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Input;
use phpDocumentor\Reflection\DocBlock\Type\Collection;
use Symfony\Component\HttpFoundation\Response;

class AjaxControll extends ExceptionController
{
	/*
	 * Global Variables are Defined Here
	 */
    public $RESULTS_PER_PAGE = 4;// This is to set number of records shown in search results page (Each)

	/*
	 * This function check whether email and username is existing or not
	 * Return Json Object with 'USING' / 'NOTHING' Keywords
	 */
    public function register_page(Request $request,$type,$data){
		try {
			if ($type == 'username') {
				/* Check for username is taken or not */
				$patients = User::whereEmail($data)->first();
			} else if ($type == 'email') {
				/* Check for email is taken or not */
				$patients = \App\Patients::whereEmail($data)->first();
			}
		}catch (Exception $e){
			$this->LogError('AjaxController Register_Page Function',$e);
		}

        if(isset($patients)){
            $res['msg'] = "USING";
        }else{
            $res['msg'] = "NOTHING";
        }

        return response()->json($res);
    }

    /*
     * This function is used for render and return doctor_results page to Ajax
     * Returns Json Object With ->
     * 		Paginated details
     * 		View results from Blade (String)
     */
    public function doc_search_page(Request $request){
		/*
		 * Normal Search DataBase Queries are Here
		 */
        if(Input::get('advanced_search') == 'NO') {

			/* This executes when Normal search is used */
            if (Input::get('filter_star_rating') == 0 && Input::get('filter_loc') == '-' && Input::get('filter_spec') == '-') {

				$query = "SELECT doc.*,spec.* FROM doctors AS doc INNER JOIN specialization AS spec on doc.id = spec.doc_id ";
				$query = $query."WHERE doc.first_name LIKE '%".Input::get('search_text_hidden')."%' OR doc.last_name LIKE '%".Input::get('search_text_hidden')."%' ORDER BY doc.id";

            } else if(Input::get('filter_star_rating') != 0) {

				$query = "SELECT doc.*,spec.* FROM doctors AS doc INNER JOIN specialization AS spec on doc.id = spec.doc_id ";
				$query = $query."WHERE doc.rating = ".Input::get('filter_star_rating')." ORDER BY doc.id";

            }else if(Input::get('filter_loc') != '-' && Input::get('filter_spec') == '-'){

				$query = "SELECT doc.*,spec.* FROM doctors AS doc INNER JOIN specialization AS spec on doc.id = spec.doc_id ";
				$query = $query."WHERE doc.district = '".Input::get('filter_loc')."' ORDER BY doc.id";

			}else if(Input::get('filter_loc') == '-' && Input::get('filter_spec') != '-'){

				$query = "SELECT doc.*,spec.* FROM doctors AS doc INNER JOIN specialization AS spec on doc.id = spec.doc_id ";
				$query = $query."WHERE spec.spec_1 = '".Input::get('specialization')."' OR spec.spec_2 = '".Input::get('specialization')."' ";
				$query = $query."OR spec.spec_3 = '".Input::get('specialization')."' OR spec.spec_4 = '".Input::get('specialization')."' OR spec.spec_5 = '".Input::get('specialization')."' ORDER BY doc.id";

			}else if(Input::get('filter_loc') != '-' && Input::get('filter_spec') != '-'){

				$query = "SELECT doc.*,spec.* FROM doctors AS doc INNER JOIN specialization AS spec on doc.id = spec.doc_id ";
				$query = $query."WHERE doc.district = '".Input::get('filter_loc')."' AND (spec.spec_1 = '".Input::get('specialization')."' OR spec.spec_2 = '".Input::get('specialization')."' ";
				$query = $query."OR spec.spec_3 = '".Input::get('specialization')."' OR spec.spec_4 = '".Input::get('specialization')."' OR spec.spec_5 = '".Input::get('specialization')."') ORDER BY doc.id";

			}
        }

		/*
		 * DataBase Array Slicing and Pagination >>>
		 */
		try {
			$all_doctors = DB::select(DB::raw($query));

			$doctors = array_slice($all_doctors, $this->RESULTS_PER_PAGE * (Input::get('page', 1) - 1), $this->RESULTS_PER_PAGE);

			$paginate_data = new LengthAwarePaginator($all_doctors, count($all_doctors), $this->RESULTS_PER_PAGE,
					Paginator::resolveCurrentPage(), ['path' => Paginator::resolveCurrentPath()]);
		}catch (Exception $e){
			$this->LogError('AjaxController Doc Search Function',$e);
		}
		/*
		 * DataBase Array Slicing and Pagination <<<
		 */

        /* This will convert view into String, Which can parse through json object */
        $HtmlView = (String) view('doctor_result')->with(['doctors'=>$doctors]);
        $res['pagination'] = $paginate_data;
        $res['page'] = $HtmlView;

        /* Return Json Type Object */
        return response()->json($res);
    }



	// This function is used for render and return doctor_results page to Ajax
	public function docAdvancedSearchPage(Request $request,$skip,$end){




			$doc_name = Input::get('advanced_doc_name');       //  get the value of the doctor nsme
			$spec = Input::get('advanced_doc_speciality');     // Get the value of the Speciality
			$treat = Input::get('advanced_doc_treatment');     // Get the value of the treatment
			$location=Input::get('advanced_doc_location');     // get the value of the location

           //if  all the features are not null this part get executed
		try{
			if($doc_name != '' && $location !='' &&  $spec != '' && $treat != '') {
				$doctors =  \DB::table('doctors')->join('treatments', 'doctors.id', '=', 'treatments.doc_id')
						->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where(function ($q3) use ($treat) {
							$q3->where('treat_1', 'like', '%' . $treat . '%')
								->orWhere('treat_2', 'like', '%' . $treat . '%')
								->orWhere('treat_3', 'like', '%' . $treat . '%')
								->orWhere('treat_4', 'like', '%' . $treat . '%')
								->orWhere('treat_5', 'like', '%' . $treat . '%');
						})
						->where(function ($q2) use ($spec) {
							$q2->where('spec_1', 'like', '%' . $spec . '%')
								->orWhere('spec_2', 'like', '%' . $spec . '%')
								->orWhere('spec_3', 'like', '%' . $spec . '%')
								->orWhere('spec_4', 'like', '%' . $spec . '%')
								->orWhere('spec_5', 'like', '%' . $spec . '%');
						})
						->where(function ($q) use ($location) {
							$q->where('address_1', 'like', '%' . $location . '%')
								->orWhere('address_2', 'like', '%' . $location . '%')
								->orWhere('city', 'like', '%' . $location . '%');
						})
						->where(function ($q4) use ($doc_name) {
							$q4->where('first_name', 'like', '%' . $doc_name . '%')
							->orWhere('last_name', 'like', '%' . $doc_name . '%');
						})->skip($skip)->take($end)->get();

				//get the count of retrieving results
                $count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN specialization ON doctors.id = specialization.doc_id INNER JOIN treatments ON doctors.id = treatments.doc_id  WHERE (treat_1 LIKE "%'.$treat.'%") AND (spec_1 LIKE "%'.$spec.'%")  AND (address_1 LIKE "%'.$location.'%")  AND (first_name LIKE "%'.$doc_name.'%" OR last_name LIKE "%'.$doc_name.'%")  ');

			}
			//if doctor name is null and others are not null go to this part
			else if ($doc_name == '' && $location != ''  &&  $spec != '' && $treat != '' ) {
				$doctors =  \DB::table('doctors')->join('treatments', 'doctors.id', '=', 'treatments.doc_id')
						->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where(function ($q3) use ($treat) {
							$q3->where('treat_1', 'like', '%' . $treat . '%')
									->orWhere('treat_2', 'like', '%' . $treat . '%')
									->orWhere('treat_3', 'like', '%' . $treat . '%')
									->orWhere('treat_4', 'like', '%' . $treat . '%')
									->orWhere('treat_5', 'like', '%' . $treat . '%');
						})
						->where(function ($q2) use ($spec) {
							$q2->where('spec_1', 'like', '%' . $spec . '%')
									->orWhere('spec_2', 'like', '%' . $spec . '%')
									->orWhere('spec_3', 'like', '%' . $spec . '%')
									->orWhere('spec_4', 'like', '%' . $spec . '%')
									->orWhere('spec_5', 'like', '%' . $spec . '%');
						})
						->where(function ($q) use ($location) {
							$q->where('address_1', 'like', '%' . $location . '%')
									->orWhere('address_2', 'like', '%' . $location . '%')
									->orWhere('city', 'like', '%' . $location . '%');
						})->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN specialization ON doctors.id = specialization.doc_id INNER JOIN treatments ON doctors.id = treatments.doc_id  WHERE (treat_1 LIKE "%'.$treat.'%") AND (spec_1 LIKE "%'.$spec.'%")  AND (address_1 LIKE "%'.$location.'%")  ');
             }
			//if doctor name and specialization is null will call this part
			else if($doc_name != '' && $location =='' &&  $spec != '' && $treat != ''){
				$doctors =  \DB::table('doctors')->join('treatments', 'doctors.id', '=', 'treatments.doc_id')
						->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where(function ($q3) use ($treat) {
							$q3->where('treat_1', 'like', '%' . $treat . '%')
									->orWhere('treat_2', 'like', '%' . $treat . '%')
									->orWhere('treat_3', 'like', '%' . $treat . '%')
									->orWhere('treat_4', 'like', '%' . $treat . '%')
									->orWhere('treat_5', 'like', '%' . $treat . '%');
						})
						->where(function ($q2) use ($spec) {
							$q2->where('spec_1', 'like', '%' . $spec . '%')
									->orWhere('spec_2', 'like', '%' . $spec . '%')
									->orWhere('spec_3', 'like', '%' . $spec . '%')
									->orWhere('spec_4', 'like', '%' . $spec . '%')
									->orWhere('spec_5', 'like', '%' . $spec . '%');
						})
						->where(function ($q4) use ($doc_name) {
							$q4->where('first_name', 'like', '%' . $doc_name . '%')
									->orWhere('last_name', 'like', '%' . $doc_name . '%');
						})->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN specialization ON doctors.id = specialization.doc_id INNER JOIN treatments ON doctors.id = treatments.doc_id  WHERE (treat_1 LIKE "%'.$treat.'%") AND (spec_1 LIKE "%'.$spec.'%") AND (first_name LIKE "%'.$doc_name.'%" OR last_name LIKE "%'.$doc_name.'%")  ');


			}
			//if doctorname and specialization are not null this part will execute
			else if($doc_name == '' && $location =='' &&  $spec != '' && $treat != ''){
				$doctors =  \DB::table('doctors')->join('treatments', 'doctors.id', '=', 'treatments.doc_id')
						->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where(function ($q3) use ($treat) {
							$q3->where('treat_1', 'like', '%' . $treat . '%')
									->orWhere('treat_2', 'like', '%' . $treat . '%')
									->orWhere('treat_3', 'like', '%' . $treat . '%')
									->orWhere('treat_4', 'like', '%' . $treat . '%')
									->orWhere('treat_5', 'like', '%' . $treat . '%');
						})
						->where(function ($q2) use ($spec) {
							$q2->where('spec_1', 'like', '%' . $spec . '%')
									->orWhere('spec_2', 'like', '%' . $spec . '%')
									->orWhere('spec_3', 'like', '%' . $spec . '%')
									->orWhere('spec_4', 'like', '%' . $spec . '%')
									->orWhere('spec_5', 'like', '%' . $spec . '%');
						})->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN specialization ON doctors.id = specialization.doc_id INNER JOIN treatments ON doctors.id = treatments.doc_id  WHERE (treat_1 LIKE "%'.$treat.'%") AND (spec_1 LIKE "%'.$spec.'%")  ');

			}
			//if secialization is null this part will execute
			else if($doc_name != '' && $location !='' &&  $spec == '' && $treat != ''){
				$doctors =  \DB::table('doctors')->join('treatments', 'doctors.id', '=', 'treatments.doc_id')
						->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where(function ($q3) use ($treat) {
							$q3->where('treat_1', 'like', '%' . $treat . '%')
									->orWhere('treat_2', 'like', '%' . $treat . '%')
									->orWhere('treat_3', 'like', '%' . $treat . '%')
									->orWhere('treat_4', 'like', '%' . $treat . '%')
									->orWhere('treat_5', 'like', '%' . $treat . '%');
						})
						->where(function ($q) use ($location) {
							$q->where('address_1', 'like', '%' . $location . '%')
									->orWhere('address_2', 'like', '%' . $location . '%')
									->orWhere('city', 'like', '%' . $location . '%');
						})
						->where(function ($q4) use ($doc_name) {
							$q4->where('first_name', 'like', '%' . $doc_name . '%')
								->orWhere('last_name', 'like', '%' . $doc_name . '%');
						})->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN treatments ON doctors.id = treatments.doc_id  WHERE (treat_1 LIKE "%'.$treat.'%") AND (address_1 LIKE "%'.$location.'%") AND (first_name LIKE "%'.$doc_name.'%" OR last_name LIKE "%'.$doc_name.'%")  ');


			}
			else if($doc_name == '' && $location !='' &&  $spec == '' && $treat != ''){
				$doctors =  \DB::table('doctors')->join('treatments', 'doctors.id', '=', 'treatments.doc_id')
						->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where(function ($q3) use ($treat) {
							$q3->where('treat_1', 'like', '%' . $treat . '%')
									->orWhere('treat_2', 'like', '%' . $treat . '%')
									->orWhere('treat_3', 'like', '%' . $treat . '%')
									->orWhere('treat_4', 'like', '%' . $treat . '%')
									->orWhere('treat_5', 'like', '%' . $treat . '%');
						})
						->where(function ($q) use ($location) {
							$q->where('address_1', 'like', '%' . $location . '%')
									->orWhere('address_2', 'like', '%' . $location . '%')
									->orWhere('city', 'like', '%' . $location . '%');
						})->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN treatments ON doctors.id = treatments.doc_id  WHERE (treat_1 LIKE "%'.$treat.'%") AND (address_1 LIKE "%'.$location.'%")  ');


			}
			else if($doc_name != '' && $location == ''  &&  $spec == '' && $treat != '' ){
				$doctors =  \DB::table('doctors')->join('treatments', 'doctors.id', '=', 'treatments.doc_id')
						->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where(function ($q3) use ($treat) {
							$q3->where('treat_1', 'like', '%' . $treat . '%')
							->orWhere('treat_2', 'like', '%' . $treat . '%')
							->orWhere('treat_3', 'like', '%' . $treat . '%')
							->orWhere('treat_4', 'like', '%' . $treat . '%')
							->orWhere('treat_5', 'like', '%' . $treat . '%');
						})
						->where(function ($q4) use ($doc_name) {
							$q4->where('first_name', 'like', '%' . $doc_name . '%')
										->orWhere('last_name', 'like', '%' . $doc_name . '%');
			            })->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN treatments ON doctors.id = treatments.doc_id  WHERE (treat_1 LIKE "%'.$treat.'%") AND (first_name LIKE "%'.$doc_name.'%" OR last_name LIKE "%'.$doc_name.'%")  ');



			}
			else if($doc_name == '' && $location =='' &&  $spec == '' && $treat != ''){
				$doctors = \DB::table('doctors')->join('specialization', 'doctors.id', '=', 'specialization.doc_id')->join('treatments', 'doctors.id', '=', 'treatments.doc_id')->where('treat_1', 'like', '%' . $treat . '%')
						->orWhere('treat_2', 'like', '%' . $treat . '%')
						->orWhere('treat_3', 'like', '%' . $treat . '%')
						->orWhere('treat_4', 'like', '%' . $treat . '%')
						->orWhere('treat_5', 'like', '%' . $treat . '%')
						->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN treatments ON doctors.id = treatments.doc_id  WHERE (treat_1 LIKE "%'.$treat.'%")   ');


			}
			else if($doc_name != '' && $location !='' &&  $spec != '' && $treat == ''){
				$doctors =  \DB::table('doctors')->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where(function ($q2) use ($spec) {
							$q2->where('spec_1', 'like', '%' . $spec . '%')
									->orWhere('spec_2', 'like', '%' . $spec . '%')
									->orWhere('spec_3', 'like', '%' . $spec . '%')
									->orWhere('spec_4', 'like', '%' . $spec . '%')
									->orWhere('spec_5', 'like', '%' . $spec . '%');
						})
						->where(function ($q) use ($location) {
							$q->where('address_1', 'like', '%' . $location . '%')
									->orWhere('address_2', 'like', '%' . $location . '%')
									->orWhere('city', 'like', '%' . $location . '%');
						})
						->where(function ($q4) use ($doc_name) {
								$q4->where('first_name', 'like', '%' . $doc_name . '%')
									->orWhere('last_name', 'like', '%' . $doc_name . '%');
						})->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN specialization ON doctors.id = specialization.doc_id  WHERE (spec_1 LIKE "%'.$spec.'%") AND (address_1 LIKE "%'.$location.'%") AND (first_name LIKE "%'.$doc_name.'%" OR last_name LIKE "%'.$doc_name.'%")  ');


			}
			else if($doc_name == '' && $location !='' &&  $spec != '' && $treat == ''){
				$doctors =  \DB::table('doctors')->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where(function ($q2) use ($spec) {
							$q2->where('spec_1', 'like', '%' . $spec . '%')
									->orWhere('spec_2', 'like', '%' . $spec . '%')
									->orWhere('spec_3', 'like', '%' . $spec . '%')
									->orWhere('spec_4', 'like', '%' . $spec . '%')
									->orWhere('spec_5', 'like', '%' . $spec . '%');
						})
						->where(function ($q) use ($location) {
							$q->where('address_1', 'like', '%' . $location . '%')
									->orWhere('address_2', 'like', '%' . $location . '%')
									->orWhere('city', 'like', '%' . $location . '%');
						})->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN specialization ON doctors.id = specialization.doc_id  WHERE (spec_1 LIKE "%'.$spec.'%") AND (address_1 LIKE "%'.$location.'%")  ');



			}
			else if($doc_name != '' && $location =='' &&  $spec != '' && $treat == ''){
				$doctors =  \DB::table('doctors')->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where(function ($q2) use ($spec) {
							$q2->where('spec_1', 'like', '%' . $spec . '%')
									->orWhere('spec_2', 'like', '%' . $spec . '%')
									->orWhere('spec_3', 'like', '%' . $spec . '%')
									->orWhere('spec_4', 'like', '%' . $spec . '%')
									->orWhere('spec_5', 'like', '%' . $spec . '%');
						})
							->where(function ($q4) use ($doc_name) {
									$q4->where('first_name', 'like', '%' . $doc_name . '%')
										->orWhere('last_name', 'like', '%' . $doc_name . '%');
							})->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN specialization ON doctors.id = specialization.doc_id  WHERE (spec_1 LIKE "%'.$spec.'%") AND (first_name LIKE "%'.$doc_name.'%" OR last_name LIKE "%'.$doc_name.'%")  ');



			}
			else if($doc_name == '' && $location =='' &&  $spec != '' && $treat == ''){
				$doctors = \DB::table('doctors')->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where('spec_1', 'like', '%' . $spec . '%')
						->orWhere('spec_2', 'like', '%' . $spec . '%')
						->orWhere('spec_3', 'like', '%' . $spec . '%')
						->orWhere('spec_4', 'like', '%' . $spec . '%')
						->orWhere('spec_5', 'like', '%' . $spec . '%')
						->skip($skip)->take($end)->get();



				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors INNER JOIN specialization ON doctors.id = specialization.doc_id  WHERE spec_1 LIKE "%'.$spec.'%" ');


			}
			else if($doc_name != '' && $location !='' &&  $spec == '' && $treat == ''){
				$doctors =  \DB::table('doctors')->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where(function ($q) use ($location) {
							$q->where('address_1', 'like', '%' . $location . '%')
							->orWhere('address_2', 'like', '%' . $location . '%')
							->orWhere('city', 'like', '%' . $location . '%');
						})
						->where(function ($q4) use ($doc_name) {
								$q4->where('first_name', 'like', '%' . $doc_name . '%')
									->orWhere('last_name', 'like', '%' . $doc_name . '%');
						})->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors WHERE (first_name LIKE "%'.$doc_name.'%" OR  last_name LIKE "%'.$doc_name.'%")  AND  (address_1 LIKE "%'.$location.'%"  OR  address_2 LIKE "%'.$location.'%" )');

			}
			else if($doc_name == '' && $location !='' &&  $spec == '' && $treat == ''){
				$doctors = \DB::table('doctors')->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
						->where('address_1', 'like', '%' . $location . '%')
						->orWhere('address_2', 'like', '%' . $location . '%')
						->orWhere('city', 'like', '%' . $location . '%')
						->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors WHERE address_1 LIKE "%'.$location.'%" OR  address_2 LIKE "%'.$location.'%" ');


			}
			else if($doc_name != '' && $location =='' &&  $spec == '' && $treat == ''){

				//caall teh method getDocname function
				$doctors =self::getDocName($doc_name,$skip,$end);

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors WHERE first_name LIKE "%'.$doc_name.'%" OR  last_name LIKE "%'.$doc_name.'%" ');

			}
			else {

				$doctors = \DB::table('doctors')->join('specialization', 'doctors.id', '=', 'specialization.doc_id')->skip($skip)->take($end)->get();

				//get the count of retrieving results
				$count1=sizeof($doctors);
				//get the count of all matching result
				$count = \DB::select('SELECT COUNT(*) AS count FROM doctors');


			}
		}catch (Exception $e){
			$this->LogError('AjaxController Register_Page Function',$e);
		}
		// This will convert view into String, Which can parse through json object
		$HtmlView = (String) view('advanced_doctor_result')->with(['doctors'=>$doctors]);
		$res['count'] = $count;
		$res['count1'] = $count1;
		$res['page'] = $HtmlView;
		// Return Json Type Object
		return response()->json($res);



	}
	public function getDocName($doc_name,$skip,$end){

		 $doctor = \DB::table('doctors')->join('specialization', 'doctors.id', '=', 'specialization.doc_id')
				 ->where(function ($q4) use ($doc_name) {
					 $q4->where('first_name', 'like', '%' . $doc_name . '%')
							 ->orWhere('last_name', 'like', '%' . $doc_name . '%');
				 })->skip($skip)->take($end)->get();
	 	return $doctor;
	}


	/*
	 * This function will get doctor comments by users
	 * Inputs Doctor`s ID
	 * Returns Json Object
	 */
    public function get_doctor_comments(Request $request,$doc_id){
		try {
			$comments = Comments::where('doctor_id', $doc_id)->orderBy('id', 'DESC')->get();

			$count = 1;

			foreach ($comments as $com) {
				$user = Patients::where('user_id', $com->user_id)->first();
				$img = Images::where('user_id', $com->user_id)->first();
				$temp['comment'] = $com;
				$temp['user'] = $user;
				$temp['user_img'] = $img;
				$comment_ob['comment_' . $count] = $temp;
				$count++;
			}
		}catch (Exception $e){
			$this->LogError('AjaxController Get_Doctor_Comments Function',$e);
		}

        if($count > 1) {
            $res['COMMENT'] = "YES";
            $res['DATA'] = $comment_ob;

            return response()->json($res);
        }else{
            $res['COMMENT'] = "NO";

            return response()->json($res);
        }
    }

	/*
	 * This function add comments into Doctor profile
	 * Return Json Object with insertion SUCCESS Keyword
	 */
    public function add_comments(Request $request){
		try {
			$doctor = Doctors::find(Input::get('doctor_id'));
			$tot_stars = ($doctor->tot_stars) + Input::get('star_rating');
			$tot_users = ($doctor->rated_tot_users) + 1;

			/* Update Doctor`s rating details */
			$doctor->rating = $tot_stars / $tot_users;
			$doctor->tot_stars = $tot_stars;
			$doctor->rated_tot_users = $tot_users;
			$doctor->save();

			/* Create Comment */
			Comments::create([
					'user_id' => Input::get('user_id'),
					'doctor_id' => Input::get('doctor_id'),
					'rating' => Input::get('star_rating'),
					'description' => Input::get('comment_description'),
					'posted_date_time' => new \DateTime()
			]);
			$res['response'] = "SUCCESS";
		}catch (Exception $e){
			$this->LogError('AjaxController Add_Comments Function',$e);
		}

        return response()->json($res);
    }

	/*
	 * This function loads personally posted comments
	 * Return Json Object with User Posted Comments
	 */
	public function get_comments_by_user(Request $request){
		$user = json_decode($_COOKIE['user'], true);

		try {
			$comments = Comments::whereUser_id($user[0]['id'])->orderBy('id', 'DESC')->limit(20)->get();

			foreach ($comments as $com) {
				$doc = Doctors::find($com->doctor_id);
				$img = Images::whereUser_id($doc->user_id)->first();
				$main_ob['com_data'] = $com;
				$main_ob['doc_first_name'] = $doc->first_name;
				$main_ob['doc_last_name'] = $doc->last_name;
				$main_ob['doc_img'] = $img->image_path;

				$res[] = $main_ob;
			}
		}catch (Exception $e){
			$this->LogError('AjaxController Get_Comments_By_User Function',$e);
		}

		return response()->json($res);
	}

	/*
	 * This function will handel chat message sending feature
	 * Returns Json Object with message send SUCCESS Keyword
	 */
	public function send_chat_message_by_user(Request $request){
		if(isset($_COOKIE['doctor_user'])){
			$user_type = "DOCTOR";
			$doc = json_decode($_COOKIE['doctor_user'], true);
			$user_id = $doc[0]['id'];// Assign logged user`s id
		} else{
			if(isset($_COOKIE['user'])){
				$user_type = "NORMAL";
				$user = json_decode($_COOKIE['user'],true);
				$user_id = $user[0]['id'];// Assign logged user`s id
			}
		}

		try {
			/* Create Chat Message */
			Chat_data::create([
					'sender_id' => $user_id,
					'receiver_id' => 0,
					'message' => Input::get('message'),
					'user_type' => $user_type,
					'posted_date_time' => new \DateTime()
			]);

			$res['response'] = "SUCCESS";
		}catch (Exception $e){
			$this->LogError('AjaxController Send_Chat_Message Function',$e);
		}

		return response()->json($res);
	}

	/*
	 * This function will get chat messages feature
	 * Return All Chat Messages by user
	 */
	public function get_chat_message_by_user(Request $request){
		if(isset($_COOKIE['doctor_user'])){
			$doc = json_decode($_COOKIE['doctor_user'], true);
			$user_id = $doc[0]['id'];// Assign logged user`s id
		} else{
			if(isset($_COOKIE['user'])){
				$user = json_decode($_COOKIE['user'],true);
				$user_id = $user[0]['id'];// Assign logged user`s id
			}
		}

		try {
			$chat_data = Chat_data::where('sender_id', '=', $user_id)->orwhere('receiver_id', '=', $user_id)->get();

			$res['chat_data'] = $chat_data;
		}catch (Exception $e){
			$this->LogError('AjaxController Get_Chat_Message_by_User Function',$e);
		}

		return response()->json($res);
	}

	/*
	 * This function is to check user and password for password reset
	 * Return Forgotten Password Check Status
	 */
	public function forgotten_password_check(Request $request){
		try {
			/* Check users table Username field */
			$user = User::whereEmail(Input::get('reset_ps_username'))->first();

			/* Check Patients table Email Field */
			$patient = Patients::whereEmail((Input::get('reset_ps_email')))->first();

			/* Check whether username and email are matching */
			if (isset($user) && isset($patient) && ($user->id == $patient->user_id)) {
				$data['CHECK'] = "OK";

				return response()->json($data);
			} else {
				/* If username or email did not match */
				if (User::whereEmail(Input::get('reset_ps_username'))->first()) {
					/* Check whether email is incorrect */
					$data['CHECK'] = "NO";
					$data['ERROR'] = "EMAIL";

					return response()->json($data);
				} else {
					/* Check whether username is incorrect */
					$data['CHECK'] = "NO";
					$data['ERROR'] = "USERNAME";

					return response()->json($data);
				}
			}
		}catch (Exception $e){
			$this->LogError('AjaxController Forgotten Password Function',$e);
		}
	}

	/*
	 * This function sends Access Code into users email to change password
	 * Return Json Object with Access_Key and Email of user
	 */
	public function forgotten_password_email(Request $request){
		try {
			/* Get Patients table First Name and Last Name Field */
			$patient = Patients::whereEmail((Input::get('reset_ps_email')))->first();

			/* Generate Random Key in Upper Case Letters with 6 characters */
			$acc_code = strtoupper(substr(md5(rand()), 0, 6));

			$subject['sub'] = "Reset Password at eAyurveda.lk";
			$subject['email'] = Input::get('reset_ps_email');
			$subject['name'] = $patient->first_name . ' ' . $patient->last_name;

			Mail::send('emails.password_reset_mail', ['access_code' => $acc_code], function ($message) use ($subject) {
				$message->to($subject['email'], $subject['name'])->subject($subject['sub']);
			});

			$data['CHECK'] = "YES";
			$data['EMAIL'] = Input::get('reset_ps_email');
			$data['ACCESS_KEY'] = $acc_code;
		}catch (Exception $e){
			$this->LogError('AjaxController Forgotten Password Email Function',$e);
		}

		return response()->json($data);
	}

	/*
	 * This function Reset Users profile password
	 * Return Json Object with Changed Keyword
	 */
	public function change_forgotten_password(Request $request){
		try {
			/* Check users table Username field */
			$user = User::whereEmail(Input::get('ch_user_name'))->first();

			/* Select patient record from table*/
			$re_patient = User::find($user->id);

			$re_patient->password = md5(Input::get('reset_ps_password'));
			$re_patient->save();

			$data['CHECK'] = "Changed";
		}catch (Exception $e){
			$this->LogError('AjaxController Change_Forgotten_Password Function',$e);
		}

		return response()->json($data);
	}

	/*
	 *  This function will return logged users details to appointment form
	 */
	public function get_user_appointment_fill(Request $request){
		try{
			$user = json_decode($_COOKIE['user'], true);
			$user_id = $user[0]['id'];
			$user_data = Patients::whereUser_id($user_id)->first();
			$res['first_name'] = $user_data->first_name;
			$res['last_name'] = $user_data->last_name;
			$res['contact_number'] = $user_data->contact_number;

			echo json_encode($res);
		}catch (Exception $e){
			$this->LogError('AjaxController Make Reservation Function',$e);
		}
	}

	/*
	 *  This function will manage Make Appointment on user request
	 *  by sending Email to registered doctors about patients details
	 */
	public function make_appointment(Request $request){
		try{

			$user = json_decode($_COOKIE['user'], true);
			$user_id = $user[0]['id'];
			$user_data = Patients::whereUser_id($user_id)->first();

			$p_district = Input::get('res_district');
			$time_slot = Input::get('res_time_slot');
			$doc_first_name = Input::get('doc_first_name');
			$doc_last_name = Input::get('doc_last_name');
			$doc_email = Input::get('doc_email');

			/* Send an Email */
			self::send_appointment_email(
					$user_data->first_name,
					$user_data->last_name,
					$user_data->contact_number,
					$user_data->email,
					$p_district,
					$time_slot,
					$doc_first_name,
					$doc_last_name,
					$doc_email
			);

			$res['CHECK'] = "SUCCESS";
			echo json_encode($res);
		}catch (Exception $e){
			$this->LogError('AjaxController Make Reservation Function',$e);
		}
	}

	/*
     * This function send email to doctors about appointments
     */
	public function send_appointment_email($p_first_name,$p_last_name,$p_contact_no,$p_email,$p_district,$time_slot,$d_first_name,$d_last_name,$d_email){
		try {
			$subject['sub'] = "Appointment Notice from eAyurveda.lk";
			$subject['email'] = $d_email;
			$subject['name'] = "Dr.".$d_first_name . " " . $d_last_name;

			Mail::send('emails.appointment_mail', ['patient_first_name' => $p_first_name, 'patient_last_name' => $p_last_name, 'patient_contact_number' => $p_contact_no, 'patient_email' => $p_email, 'patient_district' => $p_district, 'time_slot' => $time_slot] , function ($message) use ($subject) {
				$message->to($subject['email'], $subject['name'])->subject($subject['sub']);
			});
		}catch (Exception $e){
			$this->LogError('Confirmation Email Send Function',$e);
		}
	}

	/*
	 * This function will be used by Physicians Page to load Doctors Profiles
	 * First result will be pass into physicians_result.blade and render
	 * return will be rendered result in String format
	 */
	public function GetPhysiciansPaginated(Request $request){
		$results_per_page = 9;
		/*
		 * Build Up Query Depending on users selections
		 */
		if(Input::get('type') == "ALL"){
			$query = "SELECT * FROM doctors ORDER BY id";
		}else{
			$query = "SELECT * FROM doctors WHERE last_name LIKE '".Input::get('type')."%' ORDER BY id";
		}

		/*
		 * DataBase Array Slicing and Pagination >>>
		 */
		try {
			$all_doctors = DB::select(DB::raw($query));

			$doctors = array_slice($all_doctors, $results_per_page * (Input::get('page', 1) - 1), $results_per_page);

			$paginate_data = new LengthAwarePaginator($all_doctors, count($all_doctors), $results_per_page,
					Paginator::resolveCurrentPage(), ['path' => Paginator::resolveCurrentPath()]);
		}catch (Exception $e){
			$this->LogError('AjaxController GetPhysiciansPaginated Function',$e);
		}
		/*
		 * DataBase Array Slicing and Pagination <<<
		 */

		/* This will convert view into String, Which can parse through json object */
		$HtmlView = (String) view('physicians_result')->with(['doctors'=>$doctors]);
		$res['pagination'] = $paginate_data;
		$res['page'] = $HtmlView;

		/* Return Json Type Object */
		return response()->json($res);
	}

	/*
	 * This function check whether email and username is existing or not from Doctors Table
	 * Return Json Object with 'USING' / 'NOTHING' Keywords
	 */
	public function UpdateDoctorCheck(Request $request){
		$type = Input::get('type');
		$data = Input::get('data');
		try {
			if ($type == 'username') {
				/* Check for username is taken or not */
				$patients = User::whereEmail($data)->first();
			} else if ($type == 'email') {
				/* Check for email is taken or not */
				$patients = Doctors::whereEmail($data)->first();
			}
		}catch (Exception $e){
			$this->LogError('AjaxController UpdateDoctorCheck Function',$e);
		}

		if(isset($patients)){
			$res['msg'] = "USING";
		}else{
			$res['msg'] = "NOTHING";
		}

		return response()->json($res);
	}

	/*
	 * This function returns users comments on doctor
	 * which used in Doctor Account page
	 */
	public function GetCommentsOnDoctor(){
		$doc = json_decode($_COOKIE['doctor_user'], true);
		$doc_id = $doc[0]['doc_id'];// this should be replaced by $COOKIE reference

		try {
			$comments = Comments::whereDoctor_id($doc_id)->orderBy('id', 'DESC')->limit(20)->get();

			foreach ($comments as $com) {
				$pat = Patients::whereUser_id($com->user_id)->first();
				$img = Images::whereUser_id($pat->user_id)->first();
				$main_ob['com_data'] = $com;
				$main_ob['pat_first_name'] = $pat->first_name;
				$main_ob['pat_last_name'] = $pat->last_name;
				$main_ob['pat_img'] = $img->image_path;

				$res[] = $main_ob;
			}
		}catch (Exception $e){
			$this->LogError('AjaxController Get Comments On Doctor Function',$e);
		}

		return response()->json($res);
	}

	/*
	 * This function returns the View Area chart Data
	 * to the Doctor Account Page
	 */
	public function GetAreaChartOnDoc(){
		$doc = json_decode($_COOKIE['doctor_user'], true);
		$doc_id = $doc[0]['doc_id'];// this should be replaced by $COOKIE reference

		try {
			$query = "SELECT DATE(created_at) AS d,COUNT(*) AS c FROM profile_view_hits WHERE doctor_id = ".$doc_id." GROUP BY DATE(created_at) ORDER BY DATE(created_at) DESC LIMIT 5";
			$area_data = DB::select(DB::raw($query));
			$area_data = array_reverse($area_data);
			foreach ($area_data as $data) {
				$main_ob['date'] = $data->d;
				$main_ob['count'] = $data->c;

				$res[] = $main_ob;
			}
		}catch (Exception $e){
			$this->LogError('AjaxController Get Area Chart On Doc Function',$e);
		}

		return response()->json($res);
	}

	/*
         * This function returns the View Pie chart Data
         * to the Doctor Account Page
         */
	public function GetPieChartOnDoc(){
		$doc = json_decode($_COOKIE['doctor_user'], true);
		$doc_id = $doc[0]['doc_id'];// this should be replaced by $COOKIE reference

		try {
			$query = "SELECT  rating,COUNT(*) AS c FROM comments WHERE doctor_id = ".$doc_id." GROUP BY rating";
			$pie_data = DB::select(DB::raw($query));
			for($i=0;$i<5;$i++) {
				if(isset($pie_data[$i]->rating)){
					$main_ob['rating'] = $pie_data[$i]->rating;
					$main_ob['count'] = $pie_data[$i]->c;
				}else{
					$main_ob['rating'] = 0;
					$main_ob['count'] = 0;
				}
				$res[] = $main_ob;
			}
		}catch (Exception $e){
			$this->LogError('AjaxController Get Pie Chart On Doc Function',$e);
		}

		return response()->json($res);
	}

	/**
	 * Check Subscriber Email from DB
	 */
	public function CheckSubscriberEmail(Request $request){
		$checkSub = NewsLetterSubscriber::where('nsEmail','=',Input::get('email'))->first();
		if(isset($checkSub)){
			$res['result'] = "AV";
		}else{
			$res['result'] = "NO";
		}

		return response()->json($res);
	}

	/**
	 * Save News Letter Subscribers
	 */
	public function SaveNewsLetterSub(Request $request){
		/* Second -> Images Record */
		NewsLetterSubscriber::create([
			'nsEmail' => Input::get('email')
		]);

		$res['CHECK'] = "SUCCESS";
		/* Return Json Type Object */
		return response()->json($res);
	}

}
