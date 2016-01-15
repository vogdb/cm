<?php

class CM_Janus_RpcEndpoints {

    /**
     * @param string $serverKey
     * @param string $sessionData
     * @param string $channelKey
     * @param string $channelMediaId
     * @param string $channelData
     * @param string $streamKey
     * @param int $streamStart
     * @throws CM_Exception_AuthFailed
     * @throws CM_Exception_AuthRequired
     * @throws CM_Exception_Invalid
     * @throws CM_Exception_NotAllowed
     */
    public static function rpc_publish($serverKey, $sessionData, $channelKey, $channelMediaId, $channelData, $streamKey, $streamStart) {
        $janus = CM_Service_Manager::getInstance()->getJanus('janus');
        self::_authenticate($janus, $serverKey);

        $server = $janus->getConfiguration()->findServerByKey($serverKey);
        $sessionParams = CM_Params::factory(CM_Params::jsonDecode($sessionData), true);
        $session = new CM_Session($sessionParams->getString('sessionId'));
        $user = $session->getUser(true);

        $channelKey = (string) $channelKey;
        $channelMediaId = (string) $channelMediaId;
        $channelParams = CM_Params::factory(CM_Params::jsonDecode($channelData), true);
        $channelType = $channelParams->getInt('streamChannelType');

        $streamKey = (string) $streamKey;
        $streamStart = (int) $streamStart;

        $streamRepository = $janus->getStreamRepository();
        /** @var CM_Model_StreamChannel_Media $streamChannel */
        $streamChannel = $streamRepository->findStreamChannelByKey($channelKey);
        if (null === $streamChannel) {
            $streamChannel = $streamRepository->createStreamChannel($channelKey, $channelType, $server->getId(), 0, $channelMediaId);
        } elseif ($streamChannel->getType() !== $channelType) {
            throw new CM_Exception_Invalid('Passed stream channel type does not match existing');
        } elseif ($streamChannel->getMediaId() !== $channelMediaId) {
            throw new CM_Exception_Invalid('Passed stream channel mediaId does not match existing');
        } elseif ($streamChannel->getServerId() !== $server->getId()) {
            throw new CM_Exception_Invalid('Passed server does not match existing');
        }
        try {
            $streamRepository->createStreamPublish($streamChannel, $user, $streamKey, $streamStart);
        } catch (CM_Exception_NotAllowed $exception) {
            $streamChannel->delete();
            throw new CM_Exception_NotAllowed('Cannot publish: ' . $exception->getMessage());
        }
    }

    /**
     * @param string $serverKey
     * @param string $sessionData
     * @param string $channelKey
     * @param string $channelMediaId
     * @param string $channelData
     * @param string $streamKey
     * @param int $streamStart
     * @throws CM_Exception_AuthFailed
     * @throws CM_Exception_AuthRequired
     * @throws CM_Exception_Invalid
     * @throws CM_Exception_NotAllowed
     */
    public static function rpc_subscribe($serverKey, $sessionData, $channelKey, $channelMediaId, $channelData, $streamKey, $streamStart) {
        $janus = CM_Service_Manager::getInstance()->getJanus('janus');
        self::_authenticate($janus, $serverKey);

        $server = $janus->getConfiguration()->findServerByKey($serverKey);
        $sessionParams = CM_Params::factory(CM_Params::jsonDecode($sessionData), true);
        $session = new CM_Session($sessionParams->getString('sessionId'));
        $user = $session->getUser(true);

        $channelKey = (string) $channelKey;
        $channelMediaId = (string) $channelMediaId;
        $channelParams = CM_Params::factory(CM_Params::jsonDecode($channelData), true);
        $channelType = $channelParams->getInt('streamChannelType');

        $streamKey = (string) $streamKey;
        $streamStart = (int) $streamStart;

        $streamRepository = $janus->getStreamRepository();
        /** @var CM_Model_StreamChannel_Media $streamChannel */
        $streamChannel = $streamRepository->findStreamChannelByKey($channelKey);
        if (null === $streamChannel) {
            $streamChannel = $streamRepository->createStreamChannel($channelKey, $channelType, $server->getId(), 0, $channelMediaId);
        } elseif ($streamChannel->getType() !== $channelType) {
            throw new CM_Exception_Invalid('Passed stream channel type does not match existing');
        } elseif ($streamChannel->getMediaId() !== $channelMediaId) {
            throw new CM_Exception_Invalid('Passed stream channel mediaId does not match existing');
        } elseif ($streamChannel->getServerId() !== $server->getId()) {
            throw new CM_Exception_Invalid('Passed server does not match existing');
        }
        try {
            $streamRepository->createStreamSubscribe($streamChannel, $user, $streamKey, $streamStart);
        } catch (CM_Exception_NotAllowed $exception) {
            throw new CM_Exception_NotAllowed('Cannot subscribe: ' . $exception->getMessage());
        }
    }

    /**
     * @param string $serverKey
     * @param string $channelKey
     * @param string $streamKey
     * @return bool
     * @throws CM_Exception_AuthFailed
     * @throws CM_Exception_Invalid
     */
    public static function rpc_removeStream($serverKey, $channelKey, $streamKey) {
        $janus = CM_Service_Manager::getInstance()->getJanus('janus');
        self::_authenticate($janus, $serverKey);

        $server = $janus->getConfiguration()->findServerByKey($serverKey);
        $channelKey = (string) $channelKey;
        $streamKey = (string) $streamKey;

        $streamRepository = $janus->getStreamRepository();
        /** @var CM_Model_StreamChannel_Media $streamChannel */
        $streamChannel = $streamRepository->findStreamChannelByKey($channelKey);
        if (null === $streamChannel) {
            return;
        } elseif ($streamChannel->getServerId() !== $server->getId()) {
            throw new CM_Exception_Invalid('Passed server does not match existing');
        }
        $streamSubscribe = $streamChannel->getStreamSubscribes()->findKey($streamKey);
        if ($streamSubscribe) {
            $streamRepository->removeStream($streamSubscribe);
        }
        $streamPublish = $streamChannel->getStreamPublishs()->findKey($streamKey);
        if ($streamPublish) {
            $streamRepository->removeStream($streamPublish);
        }
    }

    /**
     * @param CM_Janus_Service $janus
     * @param string $serverKey
     * @throws CM_Exception_AuthFailed
     */
    protected static function _authenticate(CM_Janus_Service $janus, $serverKey) {
        if (!$janus->getConfiguration()->findServerByKey($serverKey)) {
            throw new CM_Exception_AuthFailed('Invalid serverKey');
        }
    }
}