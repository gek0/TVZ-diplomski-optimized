<?php

class MainController extends BaseController {

    /**
     * show main page
     * @return mixed
     */
	public function showMain()
	{
		return View::make('main');
	}

    /**
     * show all users from database with pagination
     * @return mixed
     */
	public function showUsers()
	{
		// disable query log
		DB::disableQueryLog();	

		// start runtime calculator
		$script_start_time = microtime(true);

		// get users by 100 records at a time
		$number_of_users = DB::table('users')->count();
		$users = User::with('university')->paginate(100);
		$number_fetched = $users->count();

		$script_end_time = microtime(true);
		$time_calculations = runtime_calc($script_end_time, $script_start_time);

		return View::make('show-users')->with(['number_of_users' => $number_of_users,
												'number_fetched' => $number_fetched,
												'users' => $users,
												'time_calculations' => $time_calculations
											]);
	}	

    /**
     * @param integer $count
     * generate and add $count users with faker database seeder to database
     * @return mixed
     */
	public function addUsers($count)
	{
		// disable query log and set hashing rounds
		DB::disableQueryLog();
		Hash::setRounds(8);

		// start runtime calculator
		$script_start_time = microtime(true);

		// create a Croatian faker
		$faker = Faker\Factory::create('hr_HR');

		//get all universities to array for later usage
		$universities = University::all();
		$universities_id_list = [];
		foreach ($universities as $university) {
			//add to array - without function call array_push
			$universities_id_list[] = $university->id;			
		}
		$first_university_id = $universities_id_list[0];
		$last_university_id = end($universities_id_list);

		// prepare array defaults
		$seeds_storage = [];

		// number of records
		$number_of_seeds = $count;

		// create random records and add to helper temp array and database
		try{
			for($i = 0; $i < $number_of_seeds; $i++){
				if($i % 2 == 0){
					$seed['first_name'] = $faker->firstNameMale;
				}
				else{
					$seed['first_name'] = $faker->firstNameFemale;
				}

				// functions called only once an multiple variables
				$randomized_num = random_number_string(15);				
				$seed['last_name'] = $faker->lastName;
				$seed['username'] = mb_strtolower(safe_name($seed['last_name']).mb_substr($seed['first_name'], 0, 1, 'utf-8')).$randomized_num;
				$seed['avatar'] = 'https://placeholdit.imgix.net/~text?txtsize=15&txt='.$seed['username'].'e&w=150&h=150';
				$seed['email'] = mb_strtolower(safe_name($seed['first_name']).'.'.safe_name($seed['last_name'])).$randomized_num.'@gmail.com';				
				$seed['password'] = Hash::make(random_string(15));
				$seed['university_id'] = rand($first_university_id, $last_university_id);

				//add to array - without function call array_push
				$seeds_storage[] = $seed;			
			}

			// bulk save to database instead an extra loop with all records
			DB::table('users')->insert($seeds_storage);
		}
		catch(Exception $e){
			$script_time_limit = ini_get('max_execution_time');
			return "Skripta je premašila vrijeme izvršavanja od ".$script_time_limit." sek.";
		}

		$script_end_time = microtime(true);
		$time_calculations = runtime_calc($script_end_time, $script_start_time);

		return View::make('add-users')->with(['seeds_storage' => $seeds_storage,
												'universities' => $universities,
												'number_of_seeds' => $number_of_seeds,
												'time_calculations' => $time_calculations
											]);
	}

    /**
     * delete all users from database
     * @return mixed
     */
	public function deleteUsers()
	{
		// disable query log and set hashing rounds
		DB::disableQueryLog();	

		// start runtime calculator
		$script_start_time = microtime(true);

		// raw query with returned value on delete
		$number_of_deleted = DB::delete("DELETE FROM `users`");

		$script_end_time = microtime(true);
		$time_calculations = runtime_calc($script_end_time, $script_start_time);

		return View::make('delete-users')->with(['number_of_deleted' => $number_of_deleted,
												'time_calculations' => $time_calculations
											]);
	}

	/**
	 * add user manually form page
	 * @return mixed
	 */
	public function addUserManualFormPage()
	{
		// disable query log and set hashing rounds
		DB::disableQueryLog();

		// start runtime calculator
		$script_start_time = microtime(true);

		//get all universities from DB to populate dropdown
		$user_universities = University::orderBy('id')->lists('university', 'id');
		$seeds_storage = '';

		$script_end_time = microtime(true);
		$time_calculations = runtime_calc($script_end_time, $script_start_time);

		return View::make('add-user-manual')->with(['seeds_storage' => $seeds_storage,
													'user_universities' => $user_universities,
													'time_calculations' => $time_calculations
		]);
	}

		/**
	 * add user manually
	 * @return mixed
	 */
	public function addUserManual()
	{
		// disable query log and set hashing rounds
		DB::disableQueryLog();
		Hash::setRounds(8);

		// start runtime calculator
		$script_start_time = microtime(true);

		if (Request::ajax()){
			//get all universities from DB to populate dropdown
			$user_universities = University::orderBy('id')->lists('university', 'id');

			//get form data
			$input_data = Input::get('formData');
			$token = Request::ajax() ? Request::header('X-CSRF-Token') : Input::get('_token');
			$user_data = ['first_name' => e($input_data['first_name']),
							'last_name' => e($input_data['last_name']),
							'username' => e($input_data['username']),
							'email' => e($input_data['email']),
							'password' => e($input_data['password']),
							'password_again' => e($input_data['password_again'])
			];

			$user_university_id = (int)e($input_data['user_university']);

			//validation
			$validator = Validator::make($user_data, User::$rules, User::$messages);

			//check if csrf token is valid
			if(Session::token() != $token){
				$script_end_time = microtime(true);
				$time_calculations = runtime_calc($script_end_time, $script_start_time);

				return Response::json(['status' => 'error',
                						'errors' => 'CSRF token is not valid.',
										'user_universities' => $user_universities,
										'time_calculations' => $time_calculations
                ]);
			}
			else{
				//check validation results and save user if ok
				if($validator->fails()){
					$script_end_time = microtime(true);
					$time_calculations = runtime_calc($script_end_time, $script_start_time);

					return Response::json(['status' => 'error',
	                						'errors' => $validator->getMessageBag()->toArray(),
											'user_universities' => $user_universities,
											'time_calculations' => $time_calculations
	                ]);
				}
				else{
					$user = new User;
					$randomized_num = random_number_string(15);
					$user->first_name = e($user_data['first_name']);
					$user->last_name = e($user_data['last_name']);
					$user->username = e($user_data['username']);
					$user->avatar = 'https://placeholdit.imgix.net/~text?txtsize=15&txt='.e($user_data['username']).'e&w=150&h=150';
					$user->email = e($user_data['email']);
					$user->password = Hash::make($user_data['password']);
					$user->university_id = $user_university_id;
					$user->save();

					$script_end_time = microtime(true);
					$time_calculations = runtime_calc($script_end_time, $script_start_time);

					return Response::json(['status' => 'success',
											'user_universities' => $user_universities,
											'time_calculations' => $time_calculations
					]);
				}
			}
		}	
		else{
            return Response::json(['status' => 'error',
                                    'errors' => 'Poslani zahtjev nije AJAX.'
            ]);
        }
	}
}
