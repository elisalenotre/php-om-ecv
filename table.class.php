<?php

// definition des classes de l'ORM

abstract class Table
{
	public static $primaryKey;
	public static $tableName;

	public static function getOne($id)
	{
		$link = mysqli_connect('localhost:3307', 'root', '', 'cinema');

		$query = 'select * from '.static::$tableName.' where '.static::$primaryKey.'='.$id;
		$res = mysqli_query($link, $query);

		$line = mysqli_fetch_assoc($res);

		return $line;
	}

	public static function getAll()
	{
		$link = mysqli_connect('localhost:3307', 'root', '', 'cinema');

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
        $link = mysqli_connect('localhost:3307', 'root', '', 'cinema');
        $query = '';

		// recup les proprietes de l'objet (id_film, titre ect)
        $fields = get_object_vars($this); 
		//on retire la primaryKey car ne doit pas être modifiee lors d'un update et est generee automatiquement
        unset($fields[static::$primaryKey]);

		//verifie si l'objet a deja un id on fait update 
        if (isset($this->{static::$primaryKey})) { 
            $setParts = []; //cree un tableau 
            foreach ($fields as $field => $value) {
				//evite les injections sql en echappant les caracteres speciaux (sécurité)
                $setParts[] = "$field = '".mysqli_real_escape_string($link, $value)."'"; 
            }
            $query = "UPDATE ".static::$tableName." SET ".implode(", ", $setParts)." WHERE ".static::$primaryKey." = ".$this->{static::$primaryKey}; //on concatene
        }

		// sinon on fait insert d'un nouvel enregistrement
		else { 
			//recupere la liste des noms de colonnes (nom, genre ect)
            $columns = implode(", ", array_keys($fields));
			//recupere les valeurs a inserer (science fiction ect)
            $values = implode("', '", array_map(fn($v) => mysqli_real_escape_string($link, $v), array_values($fields))); 
            $query = "INSERT INTO ".static::$tableName." ($columns) VALUES ('$values')";

            mysqli_query($link, $query);
			// recup de l'id auto incremente et on l'assigne a l'objet
            $this->{static::$primaryKey} = mysqli_insert_id($link); 
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

	public $id_film;
	public $titre;
	public $resum;
	public $date_debut_affiche;
	public $date_fin_affiche;
	public $duree_minutes;
	public $annee_production;
	public $id_distributeur;
	public $id_genre;

	public $distributeur;
	public $genre;

	public function __construct()
	{

	}

// HYDRATE SPECIFIQUE POUR LE FILM AVEC HYDRATION EN CHAINE

public function hydrate()
{
	// recupere les données du film avec getOne
	$data = static::getOne($this->{static::$primaryKey});
	// pour chaque donnée, on l'assigne à l'objet
	foreach ($data as $key => $value)
	{
		$this->$key = $value;
	}

	// Hydrate le distributeur
	if (isset($this->id_distributeur)) {
		$this->distributeur = new Distributeur();
		$this->distributeur->id_distributeur = $this->id_distributeur;
		$this->distributeur->hydrate();
	}

	// Hydrate le genre
	if (isset($this->id_genre)) {
		$this->genre = new Genre();
		$this->genre->id_genre = $this->id_genre;
		$this->genre->hydrate();
	}
}
}

class Genre extends Table
{
	public static $primaryKey = 'id_genre';
	public static $tableName = 'genres';

	// propriétés de l'objet - évite des dépréciations
	public $id_genre;
	public $nom;

	public function __construct()
	{

	}
}

class Distributeur extends Table
{
	public static $primaryKey = 'id_distributeur';
	public static $tableName = 'distributeurs';

	// propriétés de l'objet - évite des dépréciations
	public $id_distributeur;
	public $nom;
	public $telephone;
	public $adresse;
	public $cpostal;
	public $ville;
	public $pays;

	public function __construct()
	{

	}
}


// CODE DE L'APPLICATION

// liste de tous les films - homepage
if (!isset($_GET['page']))
{
	echo '<h1>Liste des films du cinéma</h1><br>';
	$films = Film::getAll();
	foreach ($films as $film)
	{
		echo '<a href="?page=film&id_film='.$film['id_film'].'">'.$film['titre'].'</a><br>';
	}

}

// détails d'un film
elseif($_GET['page'] == 'film')
{
	$film = Film::getOne($_GET['id_film']);

	echo '<h1>Détails du film "'.$film['titre'].'"</h1><br>';
	echo '<strong>id du film : </strong> '.$film['id_film'].'<br>';
	echo '<strong>id du genre : </strong> '.$film['id_genre'].'<br>';
	echo '<strong>id du distributeur : </strong> '.$film['id_distributeur'].'<br>';
	echo '<strong>titre du film :</strong> : '.$film['titre'].'<br>';
	echo '<strong>résumé du film :</strong> : '.$film['resum'].'<br>';
	echo '<strong>date de début d\'affichage :</strong> '.$film['date_debut_affiche'].'<br>';
	echo '<strong>date de fin d\'affichage : </strong> '.$film['date_fin_affiche'].'<br>';
	echo '<strong>durée du film (minutes) : </strong> '.$film['duree_minutes'].'<br>';
	echo '<strong>année de production : </strong> '.$film['annee_production'].'<br>';

}

// liste de tous les genres
elseif($_GET['page'] == 'genres')
{
	echo '<h1>Liste des genres de films du cinéma</h1><br>';
	$genres = Genre::getAll();
	foreach ($genres as $genre)
	{
		echo '<a href="?page=genre&id_genre='.$genre['id_genre'].'">'.$genre['nom'].'</a><br>';
	}
}

// détails d'un genre
elseif($_GET['page'] == 'genre')
{
	$genre = Genre::getOne($_GET['id_genre']);

	echo '<h1>Détails du genre de film "'.$genre['nom'].'"</h1><br>';
	echo '<strong>id du genre : </strong> '.$genre['id_genre'].'<br>';
	echo '<strong>nom du genre : </strong> '.$genre['nom'].'<br>';

}

// test de la fonction save générique
elseif($_GET['page'] == 'add_genre_raw_code')
{
	$genre = new Genre();

	$genre->nom = 'heroic fantaisie';
	$genre->save();

	echo '<pre>';
	var_dump($genre);
	echo '</pre>';

	$genre->nom = 'heroic fantaisy';
	$genre->save();

	echo '<pre>';
	var_dump($genre);
	echo '</pre>';
}

// test de la fonction hydrate
elseif($_GET['page'] == 'hydrate_film')
{
	$film = new Film;
	$film->id_film = 3571;
	$film->hydrate();

	echo '<pre>';
	var_dump($film);
	echo '</pre>';
}

?>
