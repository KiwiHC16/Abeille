<?php

    require_once(__DIR__.'/../../../core/class/AbeilleTools.class.php');
    require_once __DIR__.'/../../../../../core/php/core.inc.php';

ob_implicit_flush();

class fifo {

    var $fp;
    var $to;

    /**
     * Create a first In first Out file according to given parameters
     * fifo constructor.
     * @param $file
     * @param $mode /!\ the permissions of the created file are (mode & ~umask).
     */
    public function __construct($file, $mode, $readWrite)
    {
        if( !file_exists( $file ) )
        {
            print "creating fifo $file for $mode\n";
            if( !posix_mkfifo( $file, $mode ) )
            {
                die( "could not create named pipe $file\n" );
            }
            chown($file, "www-data");
        }
        //print "opening fifo $file for $readWrite\n";
        $this->fp = fopen( $file, $readWrite );
        // prevent fread / fwrite blocking
        stream_set_blocking($this->fp, false);
    }

    /* Cette version de la fonction read utilise 30% du CPU RPI3 donc on la remplace par la suivante ci dessous.
    function read() {
    // reads a line from a fifo file
        $line = '';
        do
        {
            $c = fgetc( $this->fp );
            if( ($c != '') and ($c != "\n") and !feof( $this->fp ) ) $line .= $c;
        } while( ($c != '') and ($c != "\n") and !feof( $this->fp ) );
        return $line;
    }
    */

    function read() {
        // reads a line from a fifo file
        $readers = array($this->fp);
        $writers = null;
        $except = null;
        if (stream_select($readers, $writers, $except, 0, 1500) == 1) {
            $line = '';
            do
            {
                $c = fgetc( $this->fp );
                if( ($c != '') and ($c != "\n") and !feof( $this->fp ) ) $line .= $c;
            } while( ($c != '') and ($c != "\n") and !feof( $this->fp ) );
            return $line;
        }
    }

    function write( $data ) {
        fputs( $this->fp, $data );
        fflush( $this->fp );
    }

    function close()
    {
        fclose($this->fp);
    }
}

?>
