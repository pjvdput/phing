<?php

namespace Phing\Type;

use Phing\Exception\BuildException;
use Phing\Project;

/**
 * This is a FileSet with the to specify permissions.
 *
 * Permissions are currently not implemented by PEAR Archive_Tar,
 * but hopefully they will be in the future.
 *
 * @package phing.tasks.ext
 */
class ZipFileSet extends FileSet
{

    private $files = null;

    /**
     *  Get a list of files and directories specified in the fileset.
     * @param bool $includeEmpty
     * @throws BuildException
     * @return array a list of file and directory names, relative to
     *               the baseDir for the project.
     */
    public function getZipFiles($includeEmpty = true)
    {

        if ($this->files === null) {

            $ds = $this->getDirectoryScanner($this->getProject());
            $this->files = $ds->getIncludedFiles();

            // build a list of directories implicitly added by any of the files
            $implicitDirs = array();
            foreach ($this->files as $file) {
                $implicitDirs[] = dirname($file);
            }

            $incDirs = $ds->getIncludedDirectories();

            // we'll need to add to that list of implicit dirs any directories
            // that contain other *directories* (and not files), since otherwise
            // we get duplicate directories in the resulting tar
            foreach ($incDirs as $dir) {
                foreach ($incDirs as $dircheck) {
                    if (!empty($dir) && $dir == dirname($dircheck)) {
                        $implicitDirs[] = $dir;
                    }
                }
            }

            $implicitDirs = array_unique($implicitDirs);

            $emptyDirectories = array();

            if ($includeEmpty) {
                // Now add any empty dirs (dirs not covered by the implicit dirs)
                // to the files array.

                foreach ($incDirs as $dir) { // we cannot simply use array_diff() since we want to disregard empty/. dirs
                    if ($dir != "" && $dir != "." && !in_array($dir, $implicitDirs)) {
                        // it's an empty dir, so we'll add it.
                        $emptyDirectories[] = $dir;
                    }
                }
            } // if $includeEmpty

            $this->files = array_merge($implicitDirs, $emptyDirectories, $this->files);
            sort($this->files);
        } // if ($this->files===null)

        return $this->files;
    }
}