<?php

declare(strict_types=1);

namespace OCA\User_Hiorg\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version000500Date20200318105554 extends SimpleMigrationStep
{

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
	{
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options)
	{
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('user_hiorg_users')) {
			$table = $schema->createTable('user_hiorg_users');
			$table->addColumn('uid', 'string', [
				'notnull' => true,
			]);
			$table->addColumn('username', 'string', [
				'notnull' => true,
			]);
			$table->addColumn('password', 'string', [
				'notnull' => true,
			]);
			$table->addColumn('displayname', 'string', [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['uid']);
		}
		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
	{
	}
}
