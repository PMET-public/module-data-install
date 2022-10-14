<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Api\Data;

interface RemoteDataPackInterface extends DataPackInterface
{
    /**
     * Get remote url
     *
     * @return string
     */
    public function getUrl();

    /**
     * Set remote url
     *
     * @param string $url
     */
    public function setUrl($url);

    /**
     * Get Auth Token
     *
     * @return string
     */
    public function getAuthToken();

    /**
     * Set name/path of data module
     *
     * @param string $token
     */
    public function setAuthToken($token);
}
