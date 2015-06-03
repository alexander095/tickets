<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

class Tickets{
	private $start;								//	Початкова точка маршруту
	private $finish;							//	Кінцева точка маршруту
	private $price = 0;							// 	Ціна маршруту
	private $allRoutes = null;					// 	Масив з інформацією маршрутів між 2-ма точками
	private $graph;								//	Масив опису графа
	private $db = null;							//	Екземпляр класу роботи з БД

	public function __construct($start, $finish){
		if($this->checkInput($start) && $this->checkInput($finish)){
			$this->start = $start;
			$this->finish = $finish;
		}else{
			throw new Exception('Wrong input!');
		}
	}

	/* З'єднання з базою даних */
	protected function dbConnect(){
		if($this->db == null){
			$this->db = new PDO('mysql:host=localhost;dbname=code', 'user', '12345');
		}
		return $this->db;
	}

	/* Перевірка даных POST */
	protected function checkInput($input){
		if(isset($input) && $input != '' && (integer)$input == 0){
			$input = strip_tags($input);
			$input = htmlspecialchars($input);
			$input = trim($input);
			return $input;
		}else{
			return false;
		}
	}

	/* Вибірка повної інформації про можливі маршрути */
	protected function getRoutesData(){
		$db = $this->dbConnect();
		$sql = "SELECT c.id, c.name, r.enable_points, r.price, r.distance
				FROM cities c
				INNER JOIN routes r ON c.id = r.city_id";
		$result = $db->query($sql);
		$this->allRoutes = $result->fetchAll(PDO::FETCH_ASSOC);
	}

	/* Пеоверне ID точки, передавши її name */
	protected function getPointId($name){
		foreach($this->allRoutes as $route){
			if($route['name'] == $name){
				return $route['id'];
			}
		}
	}

	/* Поверне масив імен точок, передавши масив їх ID */
	protected function makeNamedArray($idsArray){
		$db = $this->dbConnect();
		$newArray = array();
		$sql = "SELECT id, name FROM cities";
		$query = $db->query($sql);
		$result = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach($idsArray as $id){
			foreach($result as $point){
				if($point['id'] == $id){
					$newArray[] = $point['name'];
				}
			}
		}
		return $newArray;
	}

	/* Вибірка імен всіх точок */
	public function getPointsName(){
		$db = $this->dbConnect();
		$pointsNames = array();
		$sql = "SELECT name FROM cities";
		$query = $db->query($sql);
		while($result = $query->fetch(PDO::FETCH_NUM)){
			$pointsNames[] = $result[0];
		}
		return $pointsNames;
	}
	
	/* Перевірка введених точок на їхню присутність в масиві */
	protected function checkSearchingValues($arr, $start, $finish){
		if(array_key_exists($start, $arr) && array_key_exists($finish, $arr)){
			return true;
		}
		return false;
	}

	/* Повертає ціну повного маршруту */
	public function getPrice(){
		return $this->price;
	}
	
	/* Обчислює ціну повного маршруту */
	public function countTicketPrice($arr){	
		foreach($this->allRoutes as $route){
			for($i=0; $i<count($arr)-1; $i++){
				if($arr[$i] == $route['id'] && $arr[$i+1] == $route['enable_points']){
					//echo $route['price'].'<br />';
					$this->price += $route['price'];
				}
			}
		}
	}
	
	/* Функція запуску обробки */
	public function startSearch(){
		if($this->allRoutes == null){
			$this->getRoutesData();
		}
		
		$graph = array();
		foreach($this->allRoutes as $route){
			$graph[$route['id']][] = $route['enable_points'];
		}

		$this->graph = $graph;

		$startId = $this->getPointId($this->start);
		$finishId = $this->getPointId($this->finish);

		if(!$this->checkSearchingValues($graph, $startId, $finishId)){
			throw new Exception('Point is not found!');
		}

		$result = $this->findPath($this->graph, $startId, $finishId, null);
		$this->countTicketPrice($result);
		return $this->makeNamedArray($result);
	}

	/* Рекурсивна функція визначення шляху між заданими точками */
	protected function findPath($graph, $start, $finish, $path){
		$path[] = $start;

		if ($start == $finish){
			return $path;
		}

		$shortestRoute = array();

		foreach($graph[$start] as $node){
			if(!in_array($node, $path)){
				$newpath = $this->findPath($graph, $node, $finish, $path);
				if($newpath){
					if(!$shortestRoute || (count($newpath) < count($shortestRoute))){
						$shortestRoute = $newpath;
					}
				}
			}
		}
		return $shortestRoute;
	}

}