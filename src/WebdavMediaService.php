<?php


namespace Ogroen\MediaService {

    class WebdavMediaService implements MediaServiceInterface // test
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

        public function remove(string $sourcePath)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webdavUrl.$sourcePath);
            curl_setopt($ch, CURLOPT_USERPWD, $this->login.":".$this->password);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST  , 'DELETE');
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response) {
                throw new WebDavException($response);
            }
        }

        public function move(string $sourcePath, string $destinationPath) : MediaObject
        {
            $from = $this->webdavUrl . $sourcePath;
            $to = $this->webdavUrl . $destinationPath;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $from);
            curl_setopt($ch, CURLOPT_USERPWD,  $this->login.":".$this->password);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST  , 'MOVE');
            curl_setopt($ch, CURLOPT_HTTPHEADER,    [ 'Destination: '.$to]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response) {
                throw new WebDavException($response);
            }

            $destinationPathArray = explode('/', $destinationPath);
            $name = end($destinationPathArray);

            return new MediaObject(sprintf('%s/loaded/%s', $this->cdnUrl, $destinationPath), $name);
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

        public function clone(string $sourcePath, string $destinationPath): MediaObject
        {
            //Возможно папка не существует, что бы создать его закинем файл пустышку
            $directoryUrl = explode('/', $destinationPath);
            array_pop($directoryUrl);
            $directoryUrl = implode('/', $directoryUrl);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webdavUrl . $directoryUrl . '/dummy.txt');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_USERPWD, $this->login . ":" . $this->password);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code != 201 && $code != 204) {
                throw new WebDavException($response);
            }

            //скопируем в место назначения
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webdavUrl . $sourcePath);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "COPY");
            curl_setopt($ch, CURLOPT_USERPWD, $this->login . ":" . $this->password);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Destination:' . $this->webdavUrl . $destinationPath]);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);


            if ($code != 201 && $code != 204) {
                throw new WebDavException($response);
            }

            return new MediaObject(sprintf('%s/loaded/%s', $this->cdnUrl, $destinationPath));
        }

        public function getFileSize(string $sourcePath): int
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webdavUrl . $sourcePath);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PROPFIND");
            curl_setopt($ch, CURLOPT_USERPWD, $this->login . ":" . $this->password);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Depth: 0', 'Content-Type: application/xml']);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 207) {
                $xml = simplexml_load_string($response);
                $xml->registerXPathNamespace('d', 'DAV:');
                $result = $xml->xpath('//d:getcontentlength');

                if (!empty($result)) {
                    return $result[0];
                } else {
                    throw new WebDavException($response);
                }
            } else {
                throw new WebDavException($response);
            }
        }
    }
}