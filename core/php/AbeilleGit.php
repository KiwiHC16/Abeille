<?php

    /*
     * Abeille GIT control functions
     * For developer purposes.
     */

    $GLOBALS['rootDir'] = __DIR__."/../..";

    /* Check if plugin is a GIT repo
       Returns: true if yes, else false */
    function gitIsRepo() {
        global $rootDir;
        if (file_exists("$rootDir/.git"))
            return true;
        return false;
    }

    /* Returns current GIT branch */
    function gitGetCurrentBranch() {
        global $rootDir;
        exec("cd $rootDir; git rev-parse --abbrev-ref HEAD", $out, $ret);
        if ($ret != 0)
            return "? (err ".$ret.")";
        return $out[0];
    }

    /* GIT: Fetch --all
       '-p' => removes any local tracking for branches that no longer exist on remote.
       'turbo' = 1 => For quickest fetch to get branches list only.
     */
    // Tcharp38: Even with &>/dev/null there are still error in http.error when no internet. Why ??
    function gitFetchAll($turbo = false) {
        global $rootDir;
        if ($turbo)
            exec("cd $rootDir; sudo git fetch -p --depth 1 --all &>/dev/null", $out, $ret);
        else
            exec("cd $rootDir; sudo git fetch -p --all &>/dev/null", $out, $ret);
        if ($ret == 0)
            return true; // Ok
        return false;
    }

    /* GIT: Returns all known branches as an array */
    function gitGetAllBranches() {
        global $rootDir;
        exec("cd $rootDir; git branch --all | grep -v HEAD", $branches, $ret);
        return $branches;
    }

    /* GIT: check if there are any local modifications.
       Returns: true is modifications detected, else false */
    function gitHasLocalChanges() {
        global $rootDir;
        exec("cd $rootDir; git diff-index --quiet HEAD", $out, $ret);
        if ($ret == 0)
            return false;
        return true;
    }
?>
