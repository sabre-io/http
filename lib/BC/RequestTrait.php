<?php

namespace Sabre\HTTP\BC;

/**
 * This trait adds backwards-compatiblity features for Request objects.
 *
 * @copyright Copyright (C) 2009-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
trait RequestTrait {

    /**
     * Sets the post data.
     *
     *
     * @deprecated use setBodyParams instead.
     * @param array $postData
     * @return void
     */
    function setPostData(array $postData) {

        $this->setBodyParams($postData);

    }

    /**
     * Returns the POST data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     *
     * @deprecated use getBodyParams instead.
     * @return array
     */
    function getPostData() {

        return $this->getBodyParams();

    }

    /**
     * Returns the query parameters
     *
     * This is equivalent to PHP's $_GET superglobal.
     *
     * @deprecated use getQueryParams instead.
     * @return array
     */
    function getQueryParameters() {

        return $this->getQueryParams();

    }


}
