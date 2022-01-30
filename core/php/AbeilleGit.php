<?php

    /*
     * Abeille GIT control functions
     * For developer purposes.
     */


    /* Check if plugin is a GIT repo
       Returns: TRUE if yes, else FALSE */
    function gitIsRepo()
    {
        $Dir = __DIR__."/../../.git/";
        if (file_exists($Dir))
            return TRUE;
        return FALSE;
    }

    /* Returns current GIT branch */
    function gitGetCurrentBranch()
    {
        exec("cd plugins/Abeille; git rev-parse --abbrev-ref HEAD", $out, $ret);
        if ($ret != 0)
            return "? (err ".$ret.")";
        return $out[0];
    }

    /* GIT: Fetch --all
       '-p' => removes any local tracking for branches that no longer exist on remote.
       'turbo' = 1 => For quickest fetch to get branches list only.
     */
    // Tcharp38: Even with &>/dev/null there are still error in http.error when no internet. Why ??
    function gitFetchAll($turbo = 0)
    {
        if ($turbo)
            exec("cd plugins/Abeille; sudo git fetch -p --depth 1 --all &>/dev/null", $out, $ret);
        else
            exec("cd plugins/Abeille; sudo git fetch -p --all &>/dev/null", $out, $ret);
        if ($ret == 0)
            return true; // Ok
        return false;
    }

    /* GIT: Returns all known branches as an array */
    function gitGetAllBranches()
    {
        exec("cd plugins/Abeille; git branch --all | grep -v HEAD", $branches, $ret);
        return $branches;
    }

    /* GIT: check if there are any local modifications.
       Returns: TRUE is modifications detected, else FALSE */
    function gitHasLocalChanges()
    {
        exec("cd plugins/Abeille; git diff-index --quiet HEAD", $out, $ret);
        if ($ret == 0)
            return FALSE;
        return TRUE;
    }
?>
