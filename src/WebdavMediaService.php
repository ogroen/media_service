<?php


namespace MediaService {

    class WebdavMediaService implements MediaServiceInterface
    {
        private $cdnUrl;
        private $webdavUrl;
        private $login;
        private $password;

        public function __construct($cdnUrl, $webdavUrl, $login, $password)
        {
            $this->cdnUrl = $cdnUrl;
            $this->webdavUrl = $webdavUrl;
            $this->login = $login;
            $this->password = $password;
        }

        /**
         * @param string $filePath
         * @param array $params
         *
         * @return MediaObject
         * @throws WebDavException
         */
        public function upload($filePath, $params = []) : MediaObject
        {
            if (array_key_exists('isContent', $params) && $params['isContent']) {
                $fileContent = $filePath;

                // todo сделать проверку на размер файла
            } else {
                if (!file_exists($filePath)) {
                    throw new MediaServiceException("File {$filePath} doesn't exists");
                }

                if (filesize($filePath) > 10485760) {
                    throw new MediaServiceException("Max file size is 10485760, size of {$filePath} is ".filesize($filePath));
                }

                $fileContent = file_get_contents($filePath);
            }

            $ext = array_key_exists('extension', $params)
                ? $params['extension']
                : explode('.', $filePath)[count(explode('.', $filePath)) - 1];
            $name = array_key_exists('filename', $params)
                ? $params['filename']
                : substr(md5(time().$filePath), 0, 6).'.'.$ext;

            $objectPath = array_key_exists('dir', $params) ? $params['dir'] : 'content';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webdavUrl.$objectPath.'/'.$name);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_USERPWD, $this->login.":".$this->password);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
            $response = curl_exec($ch);

            curl_close($ch);

            if ($response) {
                throw new WebDavException($response);
            }

            return new MediaObject(sprintf('%s/loaded/%s/%s', $this->cdnUrl, $objectPath, $name), $name);
        }

        public function remove($cdnId)
        {
            // TODO: Implement remove() method.
        }

        public function getUrl(CdnFileInterface $file, $params = [])
        {
            // TODO: Implement getUrl() method.
        }

        public function getName()
        {
            return 'webdav';
        }

        public function listDir(string $name): array
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webdavUrl.$name.'/');
            curl_setopt($ch, CURLOPT_USERPWD, $this->login.":".$this->password);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);

            $list = json_decode((string) $response, true);

            curl_close($ch);

            return $list;
        }
    }

}
