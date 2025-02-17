<?php

// definition des classes de l'ORM

abstract class Table
{
	public static $primaryKey;
	public static $tableName;

	public static function getOne($id)
	{
		$link = mysqli_connect('localhost', 'root', 'root', 'cinema');

		$query = 'select * from '.static::$tableName.' where '.static::$primaryKey.'='.$id;
		$res = mysqli_query($link, $query);

		$line = mysqli_fetch_assoc($res);

		return $line;
	}

	public static function getAll()
	{
		$link = mysqli_connect('localhost', 'root', 'root', 'cinema');

		$query = 'select * from '.static::$tableName;
		$res = mysqli_query($link, $query);

		$lines = [];
		while ($line = mysqli_fetch_assoc($res))
		{
			$lines[] = $line;
		}

		return $lines;
	}

	// public function save() 
	// {
	// 	$link = mysqli_connect('localhost', 'root', 'root', 'cinema');
	// 	$query = '';

	// 	$reflect = new ReflectionClass($this);
	// 	$props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

	// 	if (isset($this->{static::$primaryKey}))
	// 	{
	// 		$query .= 'UPDATE '.static::$tableName.' SET ';
	// 		foreach ($props as $prop)
	// 		{
	// 			if ($prop->name != static::$primaryKey)
	// 			{
	// 				$query .= $prop->name.' =\''.$this->{$prop->name}.'\', ';
	// 			}
	// 		}
	// 		$query = substr($query, 0, -2);
	// 		$query .= ' WHERE '.static::$primaryKey.' = '.$this->{static::$primaryKey};
	// 		echo $query.'<br>';
	// 		$res = mysqli_query($link, $query);
	// 	}
	// 	else // sinon on genere une requete INSERT et on recupere l'id auto-incrémenté
	// 	{
	// 		$query .= 'INSERT INTO '.static::$tableName.' (';
	// 		foreach ($props as $prop)
	// 		{
	// 			$query .= $prop->name.', ';
	// 		}
	// 		$query = substr($query, 0, -2);
	// 		$query .= ') VALUES (';
	// 		foreach ($props as $prop)
	// 		{
	// 			$query .= '\''.$this->{$prop->name}.'\', ';
	// 		}
	// 		$query = substr($query, 0, -2);
	// 		$query .= ')';
	// 		$res = mysqli_query($link, $query);
	// 		echo $query.'<br>';
	// 		$pk_val = mysqli_insert_id($link);
	// 		$this->{static::$primaryKey} = $pk_val;
	// 	}
	// }

	public function hydrate()
	{
		$data = static::getOne($this->{static::$primaryKey});
		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}
	}
}

class Film extends Table
{
	public static $primaryKey = 'id_film';
	public static $tableName = 'films';

	public function __construct()
	{

	}

/*	VERSION SPECIFIQUE POUR LE FILMS

	public static function getAll()
	{
		$link = mysqli_connect('localhost', 'root', '', 'cinema');

		$query = 'select * from films';
		$res = mysqli_query($link, $query);

		$lines = [];
		while ($line = mysqli_fetch_assoc($res))
		{
			$lines[] = $line;
		}

		return $lines;
	}

	public static function getOne($id)
	{
		$link = mysqli_connect('localhost', 'root', '', 'cinema');

		$query = 'select * from films where id_film='.$id;
		$res = mysqli_query($link, $query);

		$line = mysqli_fetch_assoc($res);

		return $line;
	}


	HYDRATE SPECIFIQUE POUR LE FILM

	public function hydrate()
	{
		$data = static::getOne($this->{static::$primaryKey});
		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}
	}*/
}

class Genre extends Table
{
	public static $primaryKey = 'id_genre';
	public static $tableName = 'genres';

	public function __construct()
	{

	}

// FONCTION SAVE SPECIFIQUE POUR LE GENRE

	public function save() 
	{
		$link = mysqli_connect('localhost', 'root', 'root', 'cinema');
		$query = '';

		if (isset($this->id_genre))
		{
			$query .= 'UPDATE genres SET nom =\''.$this->nom.'\' WHERE id_genre = '.$this->id_genre;
			echo $query.'<br>';
			$res = mysqli_query($link, $query);
		}
		else // sinon on genere une requete INSERT et on recupere l'id auto-incrémenté
		{
			$query .= 'INSERT INTO genres (nom) VALUES (\''.$this->nom.'\')';
			$res = mysqli_query($link, $query);
			echo $query.'<br>';
			$pk_val = mysqli_insert_id($link);
			$this->id_genre = $pk_val;
		}
	}
}

class Distributeur extends Table
{
	public static $primaryKey = 'id_distributeur';
	public static $tableName = 'distributeurs';

	public function __construct()
	{

	}
}


// code de l'application

if (!isset($_GET['page']))
{
	echo '<h1>Liste des films du cinéma</h1><br>';
	$films = Film::getAll();
	foreach ($films as $film)
	{
		echo '<a href="?page=film&id_film='.$film['id_film'].'">'.$film['titre'].'</a><br>';
	}

}
elseif($_GET['page'] == 'film')
{
	$film = Film::getOne($_GET['id_film']);

	echo '<h1>Détails du film "'.$film['titre'].'"</h1><br>';
	echo '<pre>';
	var_dump($film);
	echo '</pre>';
}
elseif($_GET['page'] == 'genres')
{
	echo '<h1>Liste des genres de films du cinéma</h1><br>';
	$genres = Genre::getAll();
	foreach ($genres as $genre)
	{
		echo '<a href="?page=genre&id_genre='.$genre['id_genre'].'">'.$genre['nom'].'</a><br>';
	}
}
elseif($_GET['page'] == 'genre')
{
	$genre = Genre::getOne($_GET['id_genre']);

	echo '<h1>Détails du genre de film "'.$genre['nom'].'"</h1><br>';
	echo '<pre>';
	var_dump($genre);
	echo '</pre>';
}
elseif($_GET['page'] == 'add_genre_raw_code')
{
	$genre = new Genre();

	$genre->nom = 'heroic fantaisie';
	$genre->save();

	$genre->nom = 'heroic fantaisy';
	$genre->save();
}
elseif($_GET['page'] == 'hydrate_film')
{
	// 1 - rendre GENERIQUE la fonction SAVE

	// 2 - CODER EN SPECIFIQUE pour commencer PUIS en GENERIQUE la fonction HYDRATE

	$film = new Film;
	$film->id_film = 3571;
	$film->hydrate();

	//apres hydratation, le code ci-apres doit afficher toutes les valeurs des champs du films avec l'id 3571
	echo '<pre>';
	var_dump($film);
	echo '</pre>';
}


/*echo '<pre>';
var_dump($films);
echo '</pre>';*/

