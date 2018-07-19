<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 14.06.18
 * Time: 13:10
 */

namespace AppBundle\Controller;


use AppBundle\Exception\WrongInputExceptionArray;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class BaseController extends Controller
{

    /**
     * Validates an object and returns array with errors.
     * @param  $object
     * @return bool
     * @throws WrongInputExceptionArray
     */
    public function validation($object)
    {
        $validator = $this->get('validator');
        $errors = $validator->validate($object);

        if (count($errors) > 0) {
            $errorArray = array();
            foreach ($errors as $error) {
                $errorArray[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new WrongInputExceptionArray($errorArray);
        }
        return false;
    }


    /**
     * Checks whether the transmitted profiles are in the DB
     *
     * @param array $profiles
     * @param array $profilesRequest
     * @return array
     * @throws WrongInputExceptionArray
     */
    public function checkProfiles(Array $profiles, Array $profilesRequest)
    {
        $profilesDB = array();
        $profileNames = array();

        foreach ($profiles as $profile)
        {
            $profilesDB[] = $profile->getId();
            $profileNames[] = $profile->getName();
        }

        $errors = array_diff($profilesRequest, $profilesDB);

        $errorArray = array();
        foreach ($errors as $error) {
            $errorArray[] = 'The profile with the id ' . $error . ' is not present in our database.';
        }
        if(count($errorArray) > 0)
        {
            throw new WrongInputExceptionArray($errorArray);
        }
        return $profileNames;

    }

    /**
     * Checks whether all needed fields are in the request
     *
     * @param Request $request
     * @param $neededFields
     * @throws WrongInputExceptionArray
     */
    public function checkForNeededFields(Request $request, $neededFields)
    {
        foreach ($neededFields as $fieldName)
        {
            if(!$request->request->has($fieldName))
            {
                throw new WrongInputExceptionArray(
                    [$fieldName => "This field is required"]
                );
            }
        }
    }
}