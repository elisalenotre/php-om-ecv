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

	public static function getAll($limit = 10, $offset = 0)
	{
		$link = mysqli_connect('localhost', 'root', 'root', 'cinema');
	
		$query = 'SELECT * FROM '.static::$tableName.' LIMIT '.$limit.' OFFSET '.$offset;
		$res = mysqli_query($link, $query);
	
		$lines = [];
		while ($line = mysqli_fetch_assoc($res))
		{
			$lines[] = $line;
		}
	
		return $lines;
	}
	
	public static function countAll()
	{
		$link = mysqli_connect('localhost', 'root', 'root', 'cinema');
	
		$query = 'SELECT COUNT(*) as count FROM '.static::$tableName;
		$res = mysqli_query($link, $query);
	
		$line = mysqli_fetch_assoc($res);
	
		return $line['count'];
	}

	public function save()
    {
        $link = mysqli_connect('localhost', 'root', 'root', 'cinema');
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
			//recupere les valeurs a inserer (science fiction, heroic fantasy ect)
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

	public function __get($name)
    {
		// si la methode existe, on l'appelle
		// souvent utilisé dans des classes où les méthodes de type "getter" sont dynamiquement accessibles
		// cela permet d'accéder à différentes propriétés de l'objet sans avoir à écrire explicitement chaque méthode d'accès
        if (method_exists($this, 'get' . ucfirst($name))) {
            return $this->{'get' . ucfirst($name)}();
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

	private $distributeur;
	private $genre;

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

	// GETTERS POUR LES RELATIONS DE FILM
	public function getDistributeur()
	{
		if (!$this->distributeur == null && isset($this->id_distributeur)) {
			$this->distributeur = new Distributeur();
			$this->distributeur->id_distributeur = $this->id_distributeur;
			$this->distributeur->hydrate();
		}
		return $this->distributeur;
	}

	public function getGenre()
	{
		if (!$this->genre == null && isset($this->id_genre)) {
			$this->genre = new Genre();
			$this->genre->id_genre = $this->id_genre;
			$this->genre->hydrate();
		}
		return $this->genre;
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
if (!isset($_GET['page']) || $_GET['page'] == 'home')
{
    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $totalFilms = Film::countAll();
    $totalPages = ceil($totalFilms / $limit);

    echo '<h1 style="font-family: Arial, sans-serif; color: #333;">Liste des films du cinéma</h1><br>';
    $films = Film::getAll($limit, $offset);
    echo '<ul style="list-style-type: none; padding: 0;">';
    foreach ($films as $film)
    {
        echo '<li style="margin-bottom: 10px;"><a href="?page=film&id_film='.$film['id_film'].'" style="text-decoration: none; color:rgb(6, 7, 34); font-size: 18px;">'.$film['titre'].'</a></li>';
    }
    echo '</ul>';

	// affichage du select pour la pagination
	echo '<div style="footer: 0; position: absolute; bottom: 30px; width: 100%; text-align: center;">';
	echo '<label for="page-select" style="font-family: Arial, sans-serif; color: rgba(107, 107, 135, 0.46);">Choisir une page: </label>';
	echo '<select id="page-select" onchange="location = this.value;" style="padding: 5px; font-family: Arial, sans-serif; color: rgba(107, 107, 135, 0.46);">';
	for ($i = 1; $i <= $totalPages; $i++) {
		$selected = ($i == $page) ? 'selected' : '';
		echo '<option value="?page=home&p='.$i.'" '.$selected.'>'.$i.'</option>';
	}
	echo '</select>';
	echo '</div>';
}

// détails d'un film
elseif($_GET['page'] == 'film')
{
    $film = new Film();
    $film->id_film = $_GET['id_film'];
    $film->hydrate();

    echo '<h1>Détails du film "'.$film->titre.'"</h1><br>';
    echo '<strong>id du film : </strong> '.$film->id_film.'<br>';
    echo '<strong>id du genre : </strong> '.$film->id_genre.'<br>';
    echo '<strong>id du distributeur : </strong> '.$film->id_distributeur.'<br>';
    echo '<strong>titre du film :</strong> : '.$film->titre.'<br>';
    echo '<strong>résumé du film :</strong> : '.$film->resum.'<br>';
    echo '<strong>date de début d\'affichage :</strong> '.$film->date_debut_affiche.'<br>';
    echo '<strong>date de fin d\'affichage : </strong> '.$film->date_fin_affiche.'<br>';
    echo '<strong>durée du film (minutes) : </strong> '.$film->duree_minutes.'<br>';
    echo '<strong>année de production : </strong> '.$film->annee_production.'<br>';

    if ($film->genre) {
        echo '<strong>nom du genre : </strong> '.$film->genre->nom.'<br>';
    } else {
        echo '<strong>nom du genre : </strong> Non défini<br>';
    }

    if ($film->distributeur) {
        echo '<strong>nom du distributeur : </strong> '.$film->distributeur->nom.'<br>';
    } else {
        echo '<strong>nom du distributeur : </strong> Non défini<br>';
    }
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
	$film->id_film = 1;
	$film->hydrate();

	echo '<pre>';
	var_dump($film);
	echo '</pre>';
}

?>
