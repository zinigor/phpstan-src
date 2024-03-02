<?php declare(strict_types = 1);

namespace PHPStan\File;

use SplFileInfo;
use Symfony\Component\Finder\Finder;
use function file_exists;
use function implode;
use function is_file;

class FileFinder
{

	/**
	 * @param string[] $fileExtensions
	 */
	public function __construct(
		private FileExcluder $fileExcluder,
		private FileHelper $fileHelper,
		private array $fileExtensions,
	)
	{
	}

	/**
	 * @param string[] $paths
	 */
	public function findFiles(array $paths): FileFinderResult
	{
		$onlyFiles = true;
		$files = [];
		foreach ($paths as $path) {
			if (is_file($path)) {
				$files[] = $this->fileHelper->normalizePath($path);
			} elseif (!file_exists($path)) {
				throw new PathNotFoundException($path);
			} else {
				$finder = new Finder();
				//$finder->followLinks();
				$finder->files()->name('*.{' . implode(',', $this->fileExtensions) . '}');
				$finder->filter(function (SplFileInfo $file) {
					$path = $file->getPath();
					$is_it = true;
					if ( $this->fileExcluder->isExcludedFromAnalysing($path) ) {
						if(!is_file($path)) {
							echo 'Skipping ' . $path . PHP_EOL;
							if ( false !== strpos( $path, 'node_modules' ) ) {
								//die();
							} 
						}
						$is_it = false;
					}
					return $is_it;
				},
					true);
				// phpcs:disable Squiz.PHP.NonExecutableCode
				foreach ($finder->in($path) as $fileInfo) {
					$files[] = $this->fileHelper->normalizePath($fileInfo->getPathname());
					$onlyFiles = false;
				}
			}
		}

		return new FileFinderResult($files, $onlyFiles);
	}

}
