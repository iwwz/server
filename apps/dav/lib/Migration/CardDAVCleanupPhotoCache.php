<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Daniel Kesselberg (mail@danielkesselberg.de)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Migration;

use OC\Files\AppData\AppData;
use OC\Files\AppData\Factory;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ILogger;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class CardDAVCleanupPhotoCache
 *
 * This repair step removes "photo." files created by photocache
 *
 * Before https://github.com/nextcloud/server/pull/13843 a "photo." file could be created
 * for unsupported image formats by photocache. Because a file is present but not jpg, png or gif no
 * photo could be returned for this vcard. These invalid files are removed by this repair step. There is only
 * a little chance that "photo." files created again. Should be safe to remove this repair step in near future again.
 *
 * @package OCA\DAV\Migration
 */
class CardDAVCleanupPhotoCache implements IRepairStep {

	/** @var AppData */
	private $appData;

	/** @var ILogger */
	private $logger;

	public function __construct(Factory $appDataFactory, ILogger $logger) {
		$this->appData = $appDataFactory->get('dav-photocache');
		$this->logger = $logger;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Cleanup invalid photocache files for carddav';
	}

	/**
	 * @param IOutput $output
	 * @throws \OCP\Files\NotFoundException
	 */
	public function run(IOutput $output) {
		$folders = array_filter($this->appData->getDirectoryListing(), function (ISimpleFolder $folder) {
			return $folder->fileExists('photo.');
		});

		if ([] === $folders) {
			return;
		}

		$this->logger->info('Delete ' . count($folders) . '"photo." files');

		foreach ($folders as $folder) {
			try {
				/** @var ISimpleFolder $folder */
				$folder->getFile('photo.')->delete();
			} catch (\Exception $e) {
				$this->logger->logException($e);
				$this->logger->warning('Could not delete "photo." file in dav-photocache/' . $folder->getName());
			}
		}
	}
}
