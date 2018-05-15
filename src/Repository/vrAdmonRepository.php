<?php
   require __DIR__ ."/../Entity/vrAdmin.php";
   require __DIR__ ."/../Entity/vrCourse.php";
   require __DIR__ ."/../Entity/vrLink.php";
   require __DIR__ ."/../Entity/vrModule.php";
class vrAdmonRepository
{
   protected $pdo;
   protected $error;
   protected $errorDb;

    public function __construct($user=NULL,$pass=NULL) 
    {
        //$dbopts = parse_url(getenv('DATABASE_URL'));
        $dbopts=array("host"=>"localhost","port"=>5432,"path"=>"/virtualroom"); 
        $dsn = 'pgsql:
                host='.$dbopts["host"].';
                port='.$dbopts["port"].';
                dbname='.ltrim($dbopts["path"],"/");
        $default_options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        //$options = array_replace($default_options, $options);
        //parent::__construct($dsn, $username, $password, $default_options); 
        try{   
           $this->pdo= new PDO($dsn,$user,$pass,$default_options);
        }catch (PDOexception $e){
            $this->error= array("message"=>$e->getMessage(),"code"=>$e->getCode());
        }
    }
    public function hasError()
    {
        return ($this->error!=null?true:false);
    }
    public function hasErrorDb()
    {
        return ($this->errorDb!=null?true:false);        
    }
    public function getError()
    {
        return $this->error;
    }
    public function getErrorDb(string $element=null)
    {
        switch ($element){
            case "code":
            return $this->errorDb["code"];
            case "message":
            return $this->errorDb["message"];
           default:
            return $this->errorDb;
        }        
    }
    //Return true if the link was succesuflly added, otherwise false.
    public function addLink($link)
    {
        $statement=$this->pdo->prepare(
            'INSERT INTO admon."vrLink" ("Description", "URL", "ModuleID") VALUES(?, ?, ?)');
        return $statement->execute([$link->getDescription(), $link->getURL(), $link->getModuleID()]);
    }
    public function addCourse($course)
    {
        try{
            $statement=$this->pdo->prepare(
                'INSERT INTO admon."vrCourse" ("Name", "CourseID") VALUES(:name, :id)
                ON CONFLICT ("CourseID") DO UPDATE SET "Name" = :name');
            return $statement->execute([$course->getName(), $course->getCourseID()]);
        }
        catch(PDOexception $e){
            if(strstr($e->getMessage(), 'SQLSTATE[')) {
               $match= preg_match("/SQLSTATE\[(\w+)\]:(.*):(.*)/", $e->getMessage(), $matches);
                if($match>0){
                    $code = ($matches[1] == 'HT000' ? $matches[2] : $matches[1]);
                    $message = $matches[3];
                    $this->errorDb=array("message"=>$message,"code"=>$code);
                }
            }  
        }
    }
    public function addModule($module)
    {
        $statement=$this->pdo->prepare(
            'INSERT INTO admon."vrModule" ("Name", "ModuleID", "CourseID") VALUES(?, ?, ?)');
        return $statement->execute([$module->getName(), $module->getModuleID(), $module->getCourseID()]);
    }
    //getters
    public function getLinksByModuleID($moduleID)
    {
        $statement=$this->pdo->prepare(
            'Select * from admon."vrLink" Where "ModuleID"=?');
        $statement->execute([$moduleID]);
        return $statement->fetchAll(PDO::FETCH_CLASS,'vrLink');
    }
    public function getModuleByID($moduleID)
    {
        $statement=$this->pdo->prepare('Select * from admon."vrModule" Where "ModuleID"=?');
        $statement->setFetchMode(PDO::FETCH_CLASS,'vrModule');
        $statement->execute([$moduleID]);
        return $statement->fetchObject('vrModule');
    }
    public function getCourseByID($courseID)
    {
        $statement=$this->pdo->prepare(
            'Select * from admon."vrCourse" WHERE "CourseID" LIKE ?');
        $statement->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'vrCourse');
        $statement->execute([$courseID]);
        $course = $statement->fetchObject('vrCourse',[$courseID]);
        return $course;
    }
    public function run($sql, $args = NULL)
    {
        if (!$args)
        {
             return $this->pdo->query($sql);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }
    public function getCourseData($courseID)
    {
        
    }
}


