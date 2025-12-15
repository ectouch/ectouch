<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');
define('MIGRATE_VERSION_FILE', '.version');
define('MIGRATE_FILE_PREFIX', 'migrate-');
define('MIGRATE_FILE_POSTFIX', '.php');

/**
 * exp:
 * Migrate::init();
 */
class Migrate
{

    public static $version = 0;
    public static $migrate_path = '';
    public static $migrate_files = array();
    private static $conn = '';
    private static $link = '';


    static public function setPath()
    {
        self::$migrate_path = ROOT_PATH . 'data/migrates/';
    }

    public static function init()
    {
        self::setPath();
        // Find the latest version or star0t at 0.
        $f = @fopen(self::$migrate_path . MIGRATE_VERSION_FILE, 'r');
        if ($f) {
            self::$version = floatval(fgets($f));
            fclose($f);
        }
        self::update_db();
    }
    /*
     * 获取到migrate文件夹中的所有文件
     */
    // Find all the migration files in the directory and return the sorted.
    public static function get_migrations()
    {
        $dir = opendir(self::$migrate_path);
        while ($file = readdir($dir)) {
            if (substr($file, 0, strlen(MIGRATE_FILE_PREFIX)) == MIGRATE_FILE_PREFIX) {
                self::$migrate_files[] = $file;
            }
        }
        asort(self::$migrate_files);
    }

    /*
     * 根据文件名
     */
    public static function get_version_from_file($file)
    {
        return floatval(substr($file, strlen(MIGRATE_FILE_PREFIX)));
    }

    public static function update_db()
    {
        self::get_migrations();

        // Check to make sure there are no conflicts such as 2 files under the same version.
        $errors = array();
        $last_file = false;
        $last_version = false;
        foreach (self::$migrate_files as $file) {
            $file_version = self::get_version_from_file($file);
            if ($last_version !== false && $last_version === $file_version) {
                $errors[] = "$last_file --- $file";
            }
            $last_version = $file_version;
            $last_file = $file;
        }
        if (count($errors) > 0) {
            echo "Error: You have multiple files using the same version.  <br>" .
                "To resolve, move some of the files up so each one gets a unique version. <br>";
            foreach ($errors as $error) {
                echo "  $error <br>";
            }
            exit;
        }

        // Run all the new files.
        foreach (self::$migrate_files as $file) {
            $file_version = self::get_version_from_file($file);
            if ($file_version <= self::$version) {
                continue;
            }

            self::connect();
            $sqls = self::selectSql(self::$migrate_path . $file, '{pre}', C('DB_PREFIX'));

            $str = null;
            $num = 1;
            self::query('set names utf8');
            self::query('BEGIN');
            if(is_array($sqls)) {
                foreach ($sqls as $val) {
                    if (empty($val)) continue;
                    if (is_string($val)) {
                        if(!self::query($val)){
                            $num = 0;
                        }
                    }
                }
            }
            if ($num == 0) {
                self::query('ROLLBACK');
            } elseif ($num == 1) {
                self::query('COMMIT');
            }

            $version = $file_version;
            // Output the new version number.
            if(!file_put_contents(self::$migrate_path . MIGRATE_VERSION_FILE, $version)){
                echo "Failed to output new version to " . MIGRATION_VERSION_FILE . "\n";
            }
        }
    }

    public static function query($str)
    {
        if(mysqli_query(self::$conn, $str)){
            return true;
        }
        return false;
    }

    /*
     * 连接数据库方法
     */
    public static function connect()
    {
        self::$conn = mysqli_connect(C('DB_HOST'), C('DB_USER'), C('DB_PWD')) or die('Error:cannot connect to database!!!' . mysqli_error());
        self::$link = mysqli_select_db(self::$conn, C('DB_NAME')) or die('Error:fail to select!!!' . mysqli_error());
    }
    /**
     * 判断是否是注释
     * @param $sql   获取到的sql文件内容
     */
    public static function selectSql($sql_path, $old_prefix = "", $new_prefix = "", $separator = ";\n") {
        $commenter = array('#', '--');
        //判断文件是否存在
        if (!file_exists($sql_path))
            return false;

        $content = file_get_contents($sql_path);   //读取sql文件
        $content = str_replace(array($old_prefix, "\r"), array($new_prefix, "\n"), $content); //替换前缀
        //通过sql语法的语句分割符进行分割
        $segment = explode($separator, trim($content));

        //去掉注释和多余的空行
        $data = array();
        foreach ($segment as $statement) {
            $sentence = explode("\n", $statement);
            $newStatement = array();
            foreach ($sentence as $subSentence) {
                if ('' != trim($subSentence)) {
                    //判断是会否是注释
                    $isComment = false;
                    foreach ($commenter as $comer) {
                        if (preg_match("/^(" . $comer . ")/is", trim($subSentence))) {
                            $isComment = true;
                            break;
                        }
                    }
                    //如果不是注释，则认为是sql语句
                    if (!$isComment)
                        $newStatement[] = $subSentence;
                }
            }
            $data[] = $newStatement;
        }

        //组合sql语句
        foreach ($data as $statement) {
            $newStmt = '';
            foreach ($statement as $sentence) {
                $newStmt = $newStmt . trim($sentence) . "\n";
            }
            if (!empty($newStmt)) {
                $result[] = $newStmt;
            }
        }
        return $result;
    }
}