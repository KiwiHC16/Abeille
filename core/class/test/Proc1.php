<?php
    
    $sema = sem_get( 1234, 1 );
    
    if ( sem_acquire( $sema, false ) ) {
        echo "Got the semaphore\n";
    }
    else {
        echo "Can't get the semaphore\n";
    }
    
    sleep(20);
    
    if ( sem_release( $sema ) ) {
        echo "Released the semaphore\n";
    }
    else {
        echo "Can't release the semaphore\n";
    }
    ?>

