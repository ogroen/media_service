<?php


namespace Ogroen\MediaService {

    interface CdnFileInterface
    {
        /**
         * @return string
         */
        public function getCdnId();

        public function setCdnId($cdnId);

        /**
         * @return string
         */
        public function getCdnProvider();

        public function setCdnProvider($cdnProvider);

        /**
         * @return string
         */
        public function getPath();

        public function save();
    }

}
