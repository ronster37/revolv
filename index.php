<?php
	
	header('Content-type: application/json');

	//REQUESTS
	define('GET_USER_LIST', 'get-user-list');
	define('GET_USER_INFO', 'get-user-info');
	define('SELECT_SANTAS', 'select-santas');
	define('ADD_USER', 'add-user');
	define('DELETE_USER', 'delete-user');

	//RELATIONSHIP VALUES
	define('OTHER', 0);
	define('MAIN_USER', 1);
	define('CHILD', 2);
	define('SIBLING', 3);
	define('PARENT', 4);
	define('SPOUSE', 5);

	require('config.php');
	require('DatabaseManager.class.php');
	require('BindParam.class.php');
	require('SantaPair.class.php');
	require('UserInfo.class.php');

	$request_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

	//index 0 is garbage
	$url_array = parse_url($request_url);
	$full_request = explode('/', $url_array['path']);

	$request = $full_request[2];
	$param = isset($full_request[3]) ? $full_request[3] : "";

	$response = array('response' => "No Request.");

	switch ($request) {

		case GET_USER_LIST:
			//GETS ENTIRE LIST OF USERS
			$data = array();
			$db_manager = new DatabaseManager();
			$bindParam = new BindParam();
			$query = "SELECT id,name,relationship FROM santas";

			$result = $db_manager->queryForResult($query, $bindParam->get());

			if($result) {
				$response['response'] = 'Success';

				$result->bind_result($id, $name, $relationship);

				while($result->fetch()) {
					array_push($data, array('id' => $id, 'name' => $name 
											,'relationship' => $relationship));
				}

				$response['data'] = $data;

			} else {
				$response['response'] = "Error retrieving list.";
			}

			break;

		case GET_USER_INFO:
			$db_manager = new DatabaseManager();
			$bindParam = new BindParam();
			$query = "SELECT name,relationship FROM santas WHERE id = ?";

			$bindParam->add('i', $param);

			$result = $db_manager->queryForResult($query, $bindParam->get());

			if($result) {
				$response['response'] = 'Success';

				$result->bind_result($name, $relationship);

				while($result->fetch()) {
					$data = array('name' => $name,'relationship' => $relationship);
				}

				$response['data'] = $data;

			} else {
				$response['response'] = "Error retrieving list.";
			}

			break;

		case ADD_USER:
			$name = $_POST['name'];

			$relationship = $_POST['relationship'];

			$db_manager = new DatabaseManager();
			$bindParam = new BindParam();
			$query = "INSERT INTO santas(name,relationship) VALUES(?,?)";

			$bindParam->add('s', $name);
			$bindParam->add('i', $relationship);

			if($db_manager->query($query, $bindParam->get())) {
				$response['response'] = "Success";
			} else {
				$response['response'] = "Error: Count not add $name.";
			}

			break;

		case DELETE_USER:
			$db_manager = new DatabaseManager();
			$bindParam = new BindParam();
			$query = "DELETE FROM santas WHERE id = ?";

			$bindParam->add('i', $param);

			if($db_manager->query($query, $bindParam->get())) {
				$response['response'] = "Success";
			} else {
				$response['response'] = "Error: Could not delete user.";
			}

			break;

		case SELECT_SANTAS:

			$db_manager = new DatabaseManager();
			$bindParam = new BindParam();
			$prior_pairs = array();
			$response['response'] = 'Success';
			$response['data'] = array();
			$insert_into_prior_santa = array();

			//UPDATE NUMBER YEARS
			$query = "UPDATE prior_pairs SET years=years+1";

			$db_manager->query($query, $bindParam->get());

			//ALLOW SANTA-GIFTEE PAIR IF OVER THREE YEARS
			$query = "DELETE FROM prior_pairs WHERE years > 3";

			$db_manager->query($query, $bindParam->get());

			//GET PRIOR SANTAS
			$query = "SELECT santa,giftee,years FROM prior_pairs";

			$result = $db_manager->queryForResult($query, $bindParam->get());

			if($result) {

				$result->bind_result($santa, $giftee, $years);

				while($result->fetch()) {
					if(!isset($prior_pairs[$santa])) {
						$prior_pairs[$santa] = array($giftee);
					} else {
						array_push($prior_pairs[$santa], $giftee);
					}
				}

				unset($santa);
				unset($giftee);
				unset($result);
			}

			$query = "SELECT id,name,relationship FROM santas ORDER BY RAND()";

			$result = $db_manager->queryForResult($query, $bindParam->get());

			if($result) {

				$result->bind_result($id, $name, $relationship);

				$users = array();

				$count = 0;
				while($result->fetch()) {
					$users[$count] = new UserInfo($id, $name, $relationship);
					$count+=1;
				}
			}

			
			//Create another list
			//Loop through one list and select random indexed
			//person from the second list
			$potential_giftee = $users;
			$pairs = array();
			foreach($users as $santa) {

				$rand = rand(0, count($potential_giftee)-1);
				$hasGiftee = false;

				$max = 0;

				while(!$hasGiftee) {
					$giftee = $potential_giftee[$rand];
					if($santa->getId() != $giftee->getId()) {
						if(!isset($prior_pairs[$santa->getId()]) || !in_array($giftee->getId(), $prior_pairs[$santa->getId()])) {			
							array_push($pairs, array("santa" => $santa, "giftee" => $giftee));
							unset($potential_giftee[$rand]);
							$potential_giftee = array_values($potential_giftee);
							$hasGiftee = true;
						} else {
							//maybe size of list greater than 1 but only other person available
							//you have been paired in last three years so go find a switch
							foreach ($pairs as $key => $value) {
								$switch = $value['giftee'];
								$switch_santa = $value['santa'];
								if((!isset($prior_pairs[$santa->getId()]) || !in_array($switch->getId(), $prior_pairs[$santa->getId()])) && 
								   (!isset($prior_pairs[$switch_santa->getId()]) || !in_array($santa->getId(), $prior_pairs[$switch_santa->getId()]))
								   && $switch->getId() != $santa->getId()) {
									$pairs[$key]['giftee'] = $santa;
									$hasGiftee = true;
									array_push($pairs, array("santa" => $santa, "giftee" => $switch));
									unset($potential_giftee[$rand]);
									$potential_giftee = array_values($potential_giftee);
									break;
								}
							}
						}
					} else if(count($potential_giftee) == 1) {
						//if you are only one left go find a switch
						foreach ($pairs as $key => $value) {
							$switch = $value['giftee'];
							$switch_santa = $value['santa'];
							if((!isset($prior_pairs[$santa->getId()]) || !in_array($switch->getId(), $prior_pairs[$santa->getId()])) && 
							   (!isset($prior_pairs[$switch_santa->getId()]) || !in_array($santa->getId(), $prior_pairs[$switch_santa->getId()]))) {
								$pairs[$key]['giftee'] = $santa;
								$hasGiftee = true;
								array_push($pairs, array("santa" => $santa, "giftee" => $switch));
								unset($potential_giftee[$rand]);
								$potential_giftee = array_values($potential_giftee);
								break;
							}
						}
					}

					$rand = rand(0, count($potential_giftee)-1);
					// if($max > 50) { 
					// 	echo "YOLO\n";
					// 	exit();
					// }
					// echo json_encode($pairs) . "\n";
				}
			}

			//Create return json for phone
			foreach ($pairs as $pair) {
				$santa = $pair['santa'];
				$giftee = $pair['giftee'];
				array_push($response['data'] 
						   ,array("santa" => $santa->getName()
								  ,"giftee" => $giftee->getName()));

				//TODO: SHOULD BE CHECKED FOR EXCEPTION
				$bindParam = new BindParam();
				$query = "INSERT INTO prior_pairs(santa, giftee) VALUES(?,?)";
				$bindParam->add('i', $santa->getId());
				$bindParam->add('i', $giftee->getId());
				$db_manager->query($query, $bindParam->get());
			}

			break;
		
		default:
			# code...
			break;
	}

	echo json_encode($response);

?>