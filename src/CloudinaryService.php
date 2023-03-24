<?php


namespace Ogroen\MediaService {

    class CloudinaryService implements MediaServiceInterface
    {
        /**
         * @var array
         */
        private $config;

        public function __construct($config)
        {
            \Cloudinary::config([
                "cloud_name" => $config['name'],
                "api_key" => $config['key'],
                "api_secret" => $config['secret']
            ]);
            $this->config = $config;
        }

        /**
         * @param string $filePath
         * @param array $params
         * @return MediaObject
         * @throws MediaServiceException
         */
        public function upload($filePath, $params = []) : MediaObject
        {
            if (!file_exists($filePath)) {
                throw new \Exception("File {$filePath} doesn't exists");
            }

            if (filesize($filePath) > 10485760) {
                throw new \Exception("Max file size is 10485760, size of {$filePath} is ".filesize($filePath));
            }

            $res = \Cloudinary\Uploader::upload($filePath);

            if (!array_key_exists('public_id', $res)) {
                throw new MediaServiceException('Cdn file upload fail');
            }

            return new MediaObject(sprintf(
                'http://res.cloudinary.com/%s/image/upload/%s/%s',
                $this->config['name'],
                join(',', $params),
                $res['public_id']
            ), '', $res['public_id']);
        }

        public function remove($cdnId)
        {
            // TODO: Implement remove() method.
        }

        public function getUrl(CdnFileInterface $file, $params = [])
        {
            return sprintf(
                'http://res.cloudinary.com/%s/image/upload/%s/%s',
                $this->config['name'],
                join(',', $params),
                $file->getCdnId()
            );
        }

        public function getName()
        {
            return 'cloudinary';
        }

        public function listDir(string $name): array
        {
            throw new \Exception('Implement listDir() method.');

            // TODO: Implement listDir() method.
            return [];
        }
    }

}
