<?php
/**
 * Entrada Tools [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Generates some SQL to create random users for Entrada testing data.
 *
 * @author Unit: Medical Education Technology Unit
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if((!isset($_SERVER["argv"])) || (@count($_SERVER["argv"]) < 1)) {
	echo "<html>\n";
	echo "<head>\n";
	echo "	<title>Processing Error</title>\n";
	echo "</head>\n";
	echo "<body>\n";
	echo "This file should be run by command line only.";
	echo "</body>\n";
	echo "</html>\n";
	exit;
}

/*
 * This is based on the same logic that is in the installer entrada.sql file.
 */
$cohorts = array(
    (date("Y")) => 1,
	(date("Y") + 1) => 2,
	(date("Y") + 2) => 3,
	(date("Y") + 3) => 4,
	(date("Y") + 4) => 5,
);

$student_roles = array_keys($cohorts);

$user_data = array();
$user_access = array();
$group_members = array();

$firstnames = array("John", "William", "James", "Charles", "George", "Frank", "Joseph", "Thomas", "Henry", "Robert", "Edward", "Harry", "Walter", "Arthur", "Fred", "Albert", "Samuel", "David", "Louis", "Joe", "Charlie", "Clarence", "Richard", "Andrew", "Daniel", "Ernest", "Will", "Jesse", "Oscar", "Lewis", "Peter", "Benjamin", "Frederick", "Willie", "Alfred", "Sam", "Roy", "Herbert", "Jacob", "Tom", "Elmer", "Carl", "Lee", "Howard", "Martin", "Michael", "Bert", "Herman", "Jim", "Francis", "Harvey", "Earl", "Eugene", "Ralph", "Ed", "Claude", "Edwin", "Ben", "Charley", "Paul", "Edgar", "Isaac", "Otto", "Luther", "Lawrence", "Ira", "Patrick", "Guy", "Oliver", "Theodore", "Hugh", "Clyde", "Alexander", "August", "Floyd", "Homer", "Jack", "Leonard", "Horace", "Marion", "Philip", "Allen", "Archie", "Stephen", "Chester", "Willis", "Raymond", "Rufus", "Warren", "Jessie", "Milton", "Alex", "Leo", "Julius", "Ray", "Sidney", "Bernard", "Dan", "Jerry", "Calvin", "Perry", "Dave", "Anthony", "Eddie", "Amos", "Dennis", "Clifford", "Leroy", "Wesley", "Alonzo", "Garfield", "Franklin", "Emil", "Leon", "Nathan", "Harold", "Matthew", "Levi", "Moses", "Everett", "Lester", "Winfield", "Adam", "Lloyd", "Mack", "Fredrick", "Jay", "Jess", "Melvin", "Noah", "Aaron", "Alvin", "Norman", "Gilbert", "Elijah", "Victor", "Gus", "Nelson", "Jasper", "Silas", "Christopher", "Jake", "Mike", "Percy", "Adolph", "Maurice", "Cornelius", "Felix", "Reuben", "Wallace", "Claud", "Roscoe", "Sylvester", "Earnest", "Hiram", "Otis", "Simon", "Willard", "Irvin", "Mark", "Jose", "Wilbur", "Abraham", "Virgil", "Clinton", "Elbert", "Leslie", "Marshall", "Owen", "Wiley", "Anton", "Morris", "Manuel", "Phillip", "Augustus", "Emmett", "Eli", "Nicholas", "Wilson", "Alva", "Harley", "Newton", "Timothy", "Marvin", "Ross", "Curtis", "Edmund", "Jeff", "Elias", "Harrison", "Stanley", "Columbus", "Lon", "Ora", "Ollie", "Russell", "Pearl", "Solomon", "Arch", "Asa", "Clayton", "Enoch", "Irving", "Mathew", "Nathaniel", "Scott", "Hubert", "Lemuel", "Andy", "Ellis", "Emanuel", "Joshua", "Millard", "Vernon", "Wade", "Cyrus", "Miles", "Rudolph", "Sherman", "Austin", "Bill", "Chas", "Lonnie", "Monroe", "Byron", "Edd", "Emery", "Grant", "Jerome", "Max", "Mose", "Steve", "Gordon", "Abe", "Pete", "Chris", "Clark", "Gustave", "Orville", "Lorenzo", "Bruce", "Marcus", "Preston", "Bob", "Dock", "Donald", "Jackson", "Cecil", "Barney", "Delbert", "Edmond", "Anderson", "Christian", "Glenn", "Jefferson", "Luke", "Neal", "Burt", "Ike", "Myron", "Tony", "Conrad", "Joel", "Matt", "Riley", "Vincent", "Emory", "Isaiah", "Nick", "Ezra", "Green", "Juan", "Clifton", "Lucius", "Porter", "Arnold", "Bud", "Jeremiah", "Taylor", "Forrest", "Roland", "Spencer", "Burton", "Don", "Emmet", "Gustav", "Louie", "Morgan", "Ned", "Van", "Ambrose", "Chauncey", "Elisha", "Ferdinand", "General", "Julian", "Kenneth", "Mitchell", "Allie", "Josh", "Judson", "Lyman", "Napoleon", "Pedro", "Berry", "Dewitt", "Ervin", "Forest", "Lynn", "Pink", "Ruben", "Sanford", "Ward", "Douglas", "Ole", "Omer", "Ulysses", "Walker", "Wilbert", "Adelbert", "Benjiman", "Ivan", "Jonas", "Major", "Abner", "Archibald", "Caleb", "Clint", "Dudley", "Granville", "King", "Mary", "Merton", "Antonio", "Bennie", "Carroll", "Freeman", "Josiah", "Milo", "Royal", "Dick", "Earle", "Elza", "Emerson", "Fletcher", "Judge", "Laurence", "Neil", "Roger", "Seth", "Glen", "Hugo", "Jimmie", "Johnnie", "Washington", "Elwood", "Gust", "Harmon", "Jordan", "Simeon", "Wayne", "Wilber", "Clem", "Evan", "Frederic", "Irwin", "Junius", "Lafayette", "Loren", "Madison", "Mason", "Orval", "Abram", "Aubrey", "Elliott", "Hans", "Karl", "Minor", "Wash", "Wilfred", "Allan", "Alphonse", "Dallas", "Dee", "Isiah", "Jason", "Johnny", "Lawson", "Lew", "Micheal", "Orin", "Addison", "Cal", "Erastus", "Francisco", "Hardy", "Lucien", "Randolph", "Stewart", "Vern", "Wilmer", "Zack", "Adrian", "Alvah", "Bertram", "Clay", "Ephraim", "Fritz", "Giles", "Grover", "Harris", "Isom", "Jesus", "Johnie", "Jonathan", "Lucian", "Malcolm", "Merritt", "Otho", "Perley", "Rolla", "Sandy", "Tomas", "Wilford", "Adolphus", "Angus", "Arther", "Carlos", "Cary", "Cassius", "Davis", "Hamilton", "Harve", "Israel", "Leander", "Melville", "Merle", "Murray", "Pleasant", "Sterling", "Steven", "Axel", "Boyd", "Bryant", "Clement", "Erwin", "Ezekiel", "Foster", "Frances", "Geo", "Houston", "Issac", "Jules", "Larkin", "Mat", "Morton", "Orlando", "Pierce", "Prince", "Rollie", "Rollin", "Sim", "Stuart", "Wilburn", "Bennett", "Casper", "Christ", "Dell", "Egbert", "Elmo", "Fay", "Gabriel", "Hector", "Horatio", "Lige", "Saul", "Smith", "Squire", "Tobe", "Tommie", "Wyatt", "Alford", "Alma", "Alton", "Andres", "Burl", "Cicero", "Dean", "Dorsey", "Enos", "Howell", "Lou", "Loyd", "Mahlon", "Nat", "Omar", "Oran", "Parker", "Raleigh", "Reginald");
$lastnames = array("Smith", "Johnson", "Williams", "Brown", "Jones", "Miller", "Davis", "Garcia", "Rodriguez", "Wilson", "Martinez", "Anderson", "Taylor", "Thomas", "Hernandez", "Moore", "Martin", "Jackson", "Thompson", "White", "Lopez", "Lee", "Gonzalez", "Harris", "Clark", "Lewis", "Robinson", "Walker", "Perez", "Hall", "Young", "Allen", "Sanchez", "Wright", "King", "Scott", "Green", "Baker", "Adams", "Nelson", "Hill", "Ramirez", "Campbell", "Mitchell", "Roberts", "Carter", "Phillips", "Evans", "Turner", "Torres", "Parker", "Collins", "Edwards", "Stewart", "Flores", "Morris", "Nguyen", "Murphy", "Rivera", "Cook", "Rogers", "Morgan", "Peterson", "Cooper", "Reed", "Bailey", "Bell", "Gomez", "Kelly", "Howard", "Ward", "Cox", "Diaz", "Richardson", "Wood", "Watson", "Brooks", "Bennett", "Gray", "James", "Reyes", "Cruz", "Hughes", "Price", "Myers", "Long", "Foster", "Sanders", "Ross", "Morales", "Powell", "Sullivan", "Russell", "Ortiz", "Jenkins", "Gutierrez", "Perry", "Butler", "Barnes", "Fisher", "Henderson", "Coleman", "Simmons", "Patterson", "Jordan", "Reynolds", "Hamilton", "Graham", "Kim", "Gonzales", "Alexander", "Ramos", "Wallace", "Griffin", "West", "Cole", "Hayes", "Chavez", "Gibson", "Bryant", "Ellis", "Stevens", "Murray", "Ford", "Marshall", "Owens", "Mcdonald", "Harrison", "Ruiz", "Kennedy", "Wells", "Alvarez", "Woods", "Mendoza", "Castillo", "Olson", "Webb", "Washington", "Tucker", "Freeman", "Burns", "Henry", "Vasquez", "Snyder", "Simpson", "Crawford", "Jimenez", "Porter", "Mason", "Shaw", "Gordon", "Wagner", "Hunter", "Romero", "Hicks", "Dixon", "Hunt", "Palmer", "Robertson", "Black", "Holmes", "Stone", "Meyer", "Boyd", "Mills", "Warren", "Fox", "Rose", "Rice", "Moreno", "Schmidt", "Patel", "Ferguson", "Nichols", "Herrera", "Medina", "Ryan", "Fernandez", "Weaver", "Daniels", "Stephens", "Gardner", "Payne", "Kelley", "Dunn", "Pierce", "Arnold", "Tran", "Spencer", "Peters", "Hawkins", "Grant", "Hansen", "Castro", "Hoffman", "Hart", "Elliott", "Cunningham", "Knight", "Bradley", "Carroll", "Hudson", "Duncan", "Armstrong", "Berry", "Andrews", "Johnston", "Ray", "Lane", "Riley", "Carpenter", "Perkins", "Aguilar", "Silva", "Richards", "Willis", "Matthews", "Chapman", "Lawrence", "Garza", "Vargas", "Watkins", "Wheeler", "Larson", "Carlson", "Harper", "George", "Greene", "Burke", "Guzman", "Morrison", "Munoz", "Jacobs", "Obrien", "Lawson", "Franklin", "Lynch", "Bishop", "Carr", "Salazar", "Austin", "Mendez", "Gilbert", "Jensen", "Williamson", "Montgomery", "Harvey", "Oliver", "Howell", "Dean", "Hanson", "Weber", "Garrett", "Sims", "Burton", "Fuller", "Soto", "Mccoy", "Welch", "Chen", "Schultz", "Walters", "Reid", "Fields", "Walsh", "Little", "Fowler", "Bowman", "Davidson", "May", "Day", "Schneider", "Newman", "Brewer", "Lucas", "Holland", "Wong", "Banks", "Santos", "Curtis", "Pearson", "Delgado", "Valdez", "Pena", "Rios", "Douglas", "Sandoval", "Barrett", "Hopkins", "Keller", "Guerrero", "Stanley", "Bates", "Alvarado", "Beck", "Ortega", "Wade", "Estrada", "Contreras", "Barnett", "Caldwell", "Santiago", "Lambert", "Powers", "Chambers", "Nunez", "Craig", "Leonard", "Lowe", "Rhodes", "Byrd", "Gregory", "Shelton", "Frazier", "Becker", "Maldonado", "Fleming", "Vega", "Sutton", "Cohen", "Jennings", "Parks", "Mcdaniel", "Watts", "Barker", "Norris", "Vaughn", "Vazquez", "Holt", "Schwartz", "Steele", "Benson", "Neal", "Dominguez", "Horton", "Terry", "Wolfe", "Hale", "Lyons", "Graves", "Haynes", "Miles", "Park", "Warner", "Padilla", "Bush", "Thornton", "Mccarthy", "Mann", "Zimmerman", "Erickson", "Fletcher", "Mckinney", "Page", "Dawson", "Joseph", "Marquez", "Reeves", "Klein", "Espinoza", "Baldwin", "Moran", "Love", "Robbins", "Higgins", "Ball", "Cortez", "Le", "Griffith", "Bowen", "Sharp", "Cummings", "Ramsey", "Hardy", "Swanson", "Barber", "Acosta", "Luna", "Chandler", "Blair", "Daniel", "Cross", "Simon", "Dennis", "Oconnor", "Quinn", "Gross", "Navarro", "Moss", "Fitzgerald", "Doyle", "Mclaughlin", "Rojas", "Rodgers", "Stevenson", "Singh", "Yang", "Figueroa", "Harmon", "Newton", "Paul", "Manning", "Garner", "Mcgee", "Reese", "Francis", "Burgess", "Adkins", "Goodman", "Curry", "Brady", "Christensen", "Potter", "Walton", "Goodwin", "Mullins", "Molina", "Webster", "Fischer", "Campos", "Avila", "Sherman", "Todd", "Chang", "Blake", "Malone", "Wolf", "Hodges", "Juarez", "Gill", "Farmer", "Hines", "Gallagher", "Duran", "Hubbard", "Cannon", "Miranda", "Wang", "Saunders", "Tate", "Mack", "Hammond", "Carrillo", "Townsend", "Wise", "Ingram", "Barton", "Mejia", "Ayala", "Schroeder", "Hampton", "Rowe", "Parsons", "Frank", "Waters", "Strickland", "Osborne", "Maxwell", "Chan", "Deleon", "Norman", "Harrington", "Casey", "Patton", "Logan", "Bowers", "Mueller", "Glover", "Floyd", "Hartman", "Buchanan", "Cobb", "French", "Kramer", "Mccormick", "Clarke", "Tyler", "Gibbs", "Moody", "Conner", "Sparks", "Mcguire", "Leon", "Bauer", "Norton", "Pope", "Flynn", "Hogan", "Robles", "Salinas", "Yates", "Lindsey", "Lloyd", "Marsh", "Mcbride", "Owen", "Solis", "Pham", "Lang", "Pratt");

if (!isset($_SERVER["argv"][1]) || (count($_SERVER["argv"]) != 6)) {
	echo "\nUsage: ".basename(__FILE__)." <proxy_id starting value> <number of users> <organisation_id> <group> <role>\n\n";
} else {
	$proxy_id_starting_value = ((isset($_SERVER["argv"][1])) ? (int) $_SERVER["argv"][1] : 1);
	$number_of_users = ((isset($_SERVER["argv"][1])) ? (int) $_SERVER["argv"][2] : 10);

	$organisation_id = ((isset($_SERVER["argv"][2])) ? (int) $_SERVER["argv"][3] : 1);

	$group = ((isset($_SERVER["argv"][2])) ? trim($_SERVER["argv"][4]) : "student");
	$role =  ((isset($_SERVER["argv"][3])) ? trim($_SERVER["argv"][5]) : $student_roles[(count($student_roles) - 1)]);

	foreach	(range($proxy_id_starting_value, ($proxy_id_starting_value + $number_of_users)) as $proxy_id) {
        $salt = hash("sha256", (uniqid(rand(), 1) . time()));

		$user_data_row = array (
            "id" => $proxy_id,
            "number" => 0,
            "username" => $group.$proxy_id,
            "password" => sha1("password".$salt),
            "salt" => $salt,
            "organisation_id" => $organisation_id,
            "department" => "",
            "prefix" => "",
            "firstname" => $firstnames[array_rand($firstnames)],
            "lastname" => $lastnames[array_rand($lastnames)],
            "email" => $group.$proxy_id."@demo.entrada-project.org",
            "email_alt" => "",
            "email_updated" => 0,
            "google_id" => "",
            "telephone" => "",
            "fax" => "",
            "address" => "",
            "city" => "Calgary",
            "province" => "",
            "postcode" => "T2N4N1",
            "country" => "",
            "country_id" => 39,
            "province_id" => 1,
            "notes" => "",
            "office_hours" => "",
            "privacy_level" => 0,
            "copyright" => 0,
            "notifications" => 1,
//          "entry_year" => NULL,
//          "grad_year" => NULL,
            "gender" => 0,
            "clinical" => 0,
            "updated_date" => time(),
            "updated_by" => 1,
        );

		$user_access_row = array (
//          "id" => NULL,
            "user_id" => $proxy_id,
            "app_id" => 1,
            "organisation_id" => 1,
            "account_active" => 1,
            "access_starts" => time(),
            "access_expires" => 0,
            "last_login" => 0,
            "last_ip" => "",
//          "login_attempts" => NULL,
//          "locked_out_until" => NULL,
            "role" => $role,
            "group" => $group,
            "extras" => "",
            "private_hash" => md5(hash("sha256", (uniqid(rand(), 1) . time()))),
            "notes" => "",
        );

        if ($group == "student" && array_key_exists($role, $cohorts)) {
            $user_data_row["entry_year"] = ($role - 4);
            $user_data_row["grad_year"] = $role;

            $group_members_row = array (
//              "gmember_id" => NULL,
                "group_id" => $cohorts[$role],
                "proxy_id" => $proxy_id,
                "start_date" => strtotime("September 1 ".$user_data_row["entry_year"]. "00:00:00"),
                "finish_date" => strtotime("May 31 ".$user_data_row["grad_year"]. "23:59:59"),
                "member_active" => 1,
                "entrada_only" => 1,
                "updated_date" => time(),
                "updated_by" => 1,
            );

            $group_members[] = "('" . implode("','", $group_members_row) . "')";
        }

        $user_data[] = "('" . implode("','", $user_data_row) . "')";
        $user_access[] = "('" . implode("','", $user_access_row) . "')";
	}

    if (!empty($user_data) && !empty($user_access)) {
        $user_data_fields = array_keys($user_data_row);
        $user_access_fields = array_keys($user_access_row);

        echo "\n";
        echo "INSERT INTO `entrada_auth`.`user_data` (`".implode("`,`", $user_data_fields)."`) VALUES\n";
        echo implode(",\n", $user_data) . ";\n\n";

        echo "INSERT INTO `entrada_auth`.`user_access` (`".implode("`,`", $user_access_fields)."`) VALUES\n";
        echo implode(",\n", $user_access) . ";\n\n";

        if ($group == "student" && array_key_exists($role, $cohorts)) {
            $group_members_fields = array_keys($group_members_row);

            echo "INSERT INTO `entrada`.`group_members` (`".implode("`,`", $group_members_fields)."`) VALUES\n";
            echo implode(",\n", $group_members).";\n\n";
        }
	}
}
