<?php

// definition des classes de l'ORM

abstract class Table
{
	public static $primaryKey;
	public static $tableName;

	public static function getOne($id)
	{
		$link = mysqli_connect('localhost', 'root', '', 'cinema');

		$query = 'select * from '.static::$tableName.' where '.static::$primaryKey.'='.$id;
		$res = mysqli_query($link, $query);

		$line = mysqli_fetch_assoc($res);

		return $line;
	}

	public static function getAll()
	{
		$link = mysqli_connect('localhost', 'root', '', 'cinema');

		$query = 'select * from '.static::$tableName;
		$res = mysqli_query($link, $query);

		$lines = [];
		while ($line = mysqli_fetch_assoc($res))
		{
			$lines[] = $line;
		}

		return $lines;
	}

	public function save()
    {
        $link = mysqli_connect('localhost', 'root', 'root', 'cinema');
        $query = '';

        $fields = get_object_vars($this); // recup les proprietes de l'objet (id_film, titre ect)
        unset($fields[static::$primaryKey]); //on retire la primaryKey car ne doit pas être modifiee lors d'un update et est generee automatiquement

        if (isset($this->{static::$primaryKey})) { //verifie si l'objet a deja un id on fait update 
            $setParts = []; //cree un tableau 
            foreach ($fields as $field => $value) {
                $setParts[] = "$field = '".mysqli_real_escape_string($link, $value)."'"; //evite les injections sql 
            }
            $query = "UPDATE ".static::$tableName." SET ".implode(", ", $setParts)." WHERE ".static::$primaryKey." = ".$this->{static::$primaryKey}; //on concatene
        } else { // sinon on fait insert d'un nouvel enregistrement
            $columns = implode(", ", array_keys($fields));//recupere la liste des noms de colonnes (nom, genre ect)
            $values = implode("', '", array_map(fn($v) => mysqli_real_escape_string($link, $v), array_values($fields))); //recupere les valeurs a inserer (science fiction ect)
            $query = "INSERT INTO ".static::$tableName." ($columns) VALUES ('$values')";

            mysqli_query($link, $query);
            $this->{static::$primaryKey} = mysqli_insert_id($link); // recup de l'id auto incremente 
        }
        echo $query;
        mysqli_query($link, $query);
        echo $query.'<br>';
    }

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

	public function save() 
	{
		$link = mysqli_connect('localhost', 'root', '', 'cinema');
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

	//apres hydratation, le cod ci-apres doit afficher toutes les valeurs des champs du films avec l'id 3571
	echo '<pre>';
	var_dump($film);
	echo '</pre>';
}


/*echo '<pre>';
var_dump($films);
echo '</pre>';*/

