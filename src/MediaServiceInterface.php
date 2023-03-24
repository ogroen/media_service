<?php


namespace Ogroen\MediaService {

    interface MediaServiceInterface
    {
        public function getName();

        /**
         * @param string $filePath
         * @param array $params - isContent|extension|dir|filename
         *
         * @throws MediaServiceException
         *
         * @return MediaObject
         */
        public function upload($filePath, $params = []) : MediaObject;

        public function remove($cdnId);

        /**
         * @param CdnFileInterface $file
         * @param array $params
         * @return string
         */
        public function getUrl(CdnFileInterface $file, $params = []);

        /**
         * @param $name
         * @return array
         */
        public function listDir(string $name) : array;
    }

}
