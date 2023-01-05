<?php
/**
 * Omeka
 * 
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * This abstract class encapsulates all the behavior that facilitates file 
 * ingest based on the assumption that each file can be retrieved via a string 
 * containing both the name and location of that file.  
 * 
 * Applies to: URLs, file paths on a server. Does not apply to: direct HTTP 
 * uploads.
 * 
 * Also, if the original filename is not properly represented by the source 
 * identifier (incorrect file extension, etc.), a more accurate filename can be
 * provided via the 'filename' attribute.
 * 
 * @package Omeka\File\Ingest
 */
abstract class Omeka_File_Ingest_AbstractSourceIngest extends Omeka_File_Ingest_AbstractIngest
{
    /**
     * The 'source' key of the file info is parsed out by default.
     * 
     * @param array File info array.
     * @return string
     */
    protected function _getFileSource($fileInfo)
    {
        return $fileInfo['source'];
    }

    /**
     * Normalize a file info array.
     *
     * Files can be represented as one of the following: 
     * - a string, representing the source identifier for a single file. 
     * - an array containing a 'source' key.
     * - an array of strings.
     * - an array of arrays that each contain a 'source' key.
     * 
     * @param string|array $files
     * @return array Formatted info array.
     */
    protected function _parseFileInfo($files)
    {
        $infoArray = array();

        if (is_array($files)) {
            // If we have an array representing a single source.
            if (array_key_exists('source', $files)) {
                $infoArray = array($files);
            } else {
                foreach ($files as $key => $value) {
                    // Convert an array of strings, an array of arrays, or a
                    // mix of the two, into an array of arrays.
                    $infoArray[$key] = !is_array($value)
                                          ? array('source' => $value)
                                          : $value;
                }
            }
        // If it's a string, make sure that represents the 'source' attribute.
        } elseif (is_string($files)) {
            $infoArray = array(array('source' => $files));
        } else {
            throw new Omeka_File_Ingest_Exception('File info is incorrectly formatted!');
        }

        return $infoArray;
    }

    /**
     * Modify the set of info about each file to ensure that it is compatible
     * with the Zend_Validate_File_* validators.
     *
     * @param array $fileInfo
     * @return array 
     */
    private function _addZendValidatorAttributes(array $fileInfo)
    {
        // Need to populate the 'name' field with either the filename provided
        // by the user or generated by the class.
        if (!array_key_exists('name', $fileInfo)) {
            $fileInfo['name'] = $this->_getOriginalFilename($fileInfo);
        }

        return $fileInfo;
    }

    /**
     * Retrieve the original filename.
     * 
     * By default, this is stored as the 'name' attribute in the array.
     * 
     * @todo May be able to factor this out entirely to use the 'name' attribute
     * of the file info array.
     * @param array $fileInfo
     * @return string
     */
    protected function _getOriginalFilename($fileInfo)
    {
        if (array_key_exists('name', $fileInfo)) {
            return $fileInfo['name'];
        }
    }

    /**
     * Transfer the file to Omeka.
     * 
     * @param array $fileInfo
     * @param string $originalFilename
     * @return string Path to file in Omeka.
     */
    protected function _transferFile($fileInfo, $originalFilename)
    {
        $fileSourcePath = $this->_getFileSource($fileInfo);
        $this->_validateSource($fileSourcePath, $fileInfo);

        // The final destination of the file in Omeka.
        $fileDestinationPath = $this->_getDestination($originalFilename);

        // Transfer to the temp directory.
        $this->_transfer($fileSourcePath, $fileDestinationPath, $fileInfo);

        // Adjust the file info array so that it works with the Zend Framework
        // validation.
        $fileInfo = $this->_addZendValidatorAttributes($fileInfo);

        // If the transferred file is invalid, delete it.
        try {
            $this->_validateFile($fileDestinationPath, $fileInfo);
        } catch (Omeka_File_Ingest_InvalidException $e) {
            unlink($fileDestinationPath);
            throw $e;
        }

        return $fileDestinationPath;
    }

    /**
     * Transfer the file from the original location to its destination.
     * 
     * Examples would include transferring the file via wget, or making use of
     * stream wrappers to copy the file.
     * 
     * @see _transferFile()
     * @param string $source
     * @param string $destination
     * @param array $fileInfo
     */
    abstract protected function _transfer($source, $destination, array $fileInfo);

    /**
     * Determine whether or not the file source is valid.  
     * 
     * Examples of this would include determining whether a URL exists, or
     * whether read access is available for a given file.
     * 
     * @param string $source
     * @param array $info
     * @throws Omeka_File_Ingest_InvalidException
     */
    abstract protected function _validateSource($source, $info);
}