<?php

// definition des classes de l'ORM

abstract class Table
{
	public static $primaryKey;
	public static $tableName;

	public static function getOne($id)
	{
		$link = mysqli_connect('localhost', '', '', 'cinema');

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

	public function getDataObject(): array
    {
        return array_diff_key(get_object_vars($this), get_class_vars(get_class()));
    }



	public function save() 
	{
		$link = mysqli_connect('localhost', 'root', '', 'cinema');
		$query = '';
		$data = $this->getDataObject();
		unset($data['id']);

		if ($this->getId())
		{
			$query = 'update '. $this->tableName . " set ";
            foreach ($data as $column => $value) {
                $query .= $column . "=:" . $column . ",";
            }
            $query = substr($query, 0, -1);
            $query .= " where id = " . $this->getId();			
			echo $query.'<br>';
			$res = mysqli_query($link, $query);
		}
		else // sinon on genere une requete INSERT et on recupere l'id auto-incrémenté
		{
			$query .= 'insert into '.static::$tableName . "(" . implode(",", array_keys($data)) . ") values (:" . implode(",:", array_keys($data)) . ")";
			$res = mysqli_query($link, $query);
			echo $query.'<br>';
			$pk_val = mysqli_insert_id($link);
			//$this->array_keys($data)= $pk_val;
		}
		if (!empty($this->getId()));

	}

	//

			
      

       // $queryPrepared = $this->pdo->prepare($sql);
       // $queryPrepared->execute($data);
}

class Film extends Table
{
	public static $primaryKey = 'id_film';
	public static $tableName = 'films';

	public int $id = 0;
    public int $id_genre = 0;
    public int $id_distributeur = 0;
    public string $titre = "";
    public string $resum = "";
    public int $duree_minutes = 0;
    public int $annee_production = 0;
    public \DateTime $date_debut_affiche;
    public \DateTime $date_fin_affiche;



	public function __construct()
	{
		$this->id = $id;
        $this->id_genre = $id_genre;
        $this->id_distributeur = $id_distributeur;
        $this->titre = $titre;
        $this->resum = $resum;
        $this->duree_minutes = $duree_minutes;
        $this->annee_production = $annee_production;

	}
}

class Genre extends Table
{
	public static $primaryKey = 'id_genre';
	public static $tableName = 'genres';

	public function __construct()
	{

	}
}

	/*public function save() 
	{
		$link = mysqli_connect('localhost', 'root', '', 'cinema');
		$query = '';

		if (isset($this->id_genre))
		{
			$query .= 'UPDATE'. $tableName .'SET nom =\''.$this->nom.'\' WHERE'. $primary_key.' = '.$this->id_genre;
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
}*/

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

	//apres hydratation, le cod ci-apres doit afficher toutes les valeurs des champs du films avec l'id 3571
	echo '<pre>';
	var_dump($film);
	echo '</pre>';
}


/*echo '<pre>';
var_dump($films);
echo '</pre>';*/

