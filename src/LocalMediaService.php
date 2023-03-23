<?php


namespace MediaService {

    class LocalMediaService implements MediaServiceInterface
    {
        /**
         * @var string
         */
        private $filesPath;

        /**
         * @var string
         */
        private $cdnUrl;

        public function __construct($cdnUrl, $path)
        {
            $this->filesPath = $path;

            if (!file_exists($path)) {
                throw new \Exception('Path "'.$path.'" is invalid!');
            }
            $this->cdnUrl = $cdnUrl;
        }

        /**
         * @param string $filePath
         * @param array $params
         * @return MediaObject
         * @throws MediaServiceException
         */
        public function upload($filePath, $params = []) : MediaObject
        {
            if (array_key_exists('isContent', $params)) {
                if (!file_exists($filePath)) {
                    throw new MediaServiceException("File {$filePath} doesn't exists");
                }

                if (filesize($filePath) > 10485760) {
                    throw new MediaServiceException("Max file size is 10485760, size of {$filePath} is ".filesize($filePath));
                }

                $fileContent = file_get_contents($filePath);
            } else {
                $fileContent = $filePath;

                // todo сделать проверку на размер файла
            }

            $ext = array_key_exists('extension', $params)
                ? $params['extension']
                : explode('.', $filePath)[count(explode('.', $filePath)) - 1];
            $name = substr(hash_file('md5', $filePath), 0, 6).'.'.$ext;

            $objectPath = array_key_exists('dir', $params) ? $params['dir'] : 'content';
            $path = sprintf('%s/%s/', $this->filesPath, $objectPath);

            if (!file_exists($path)) {
                mkdir($path, 0775);
            }

            if (array_key_exists('isContent', $params)) {
                file_put_contents($path.$name, $fileContent);
            } else {
                copy($filePath, $path.$name);
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
            return 'local';
        }

        public function listDir(string $name): array
        {
            throw new \Exception('Implement listDir() method.');

            // TODO: Implement listDir() method.
            return [];
        }
    }

}
