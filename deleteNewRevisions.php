<?php
/**
 * Delete new (post-August 9, 2019) revisions from the database
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 */

require_once __DIR__ . '/Maintenance.php';

/**
 * Maintenance script that deletes new revisions (post-August 9, 2019) from the database.
 *
 * @ingroup Maintenance
 */
class DeleteNewRevisions extends Maintenance {
    public function __construct() {
        parent::__construct();
        $this->addDescription( 'Delete new (post-August 9, 2019) revisions from the database' );
        $this->addOption( 'delete', 'Actually perform the deletion' );
        $this->addOption( 'page_id', 'List of page ids to work on', false );
    }

    public function execute() {
        $this->output( "Delete new revisions (after August 9, 2019)\n\n" );
        $this->doDelete( $this->hasOption( 'delete' ), $this->getArgs() );
    }

    private function doDelete( $delete = false, $pageIds = [] ) {
        # Data should come off the master, wrapped in a transaction
        $dbw = $this->getPrimaryDB();
        $this->beginTransaction( $dbw, __METHOD__ );

        $pageConds = [];
        $revConds = [];

        # If a list of page_ids was provided, limit results to that set of page_ids
        if ( count( $pageIds ) > 0 ) {
            $pageConds['page_id'] = $pageIds;
            $revConds['rev_page'] = $pageIds;
            $this->output( "Limiting to page IDs " . implode( ',', $pageIds ) . "\n" );
        }

        # Find revisions created after August 9, 2019
        $this->output( "Searching for new revisions..." );
        $res = $dbw->newSelectQueryBuilder()
            ->select( 'rev_id' )
            ->from( 'revision' )
            ->where( array_merge( $revConds, [ 'rev_timestamp > ' . $dbw->addQuotes('20190809000000') ] ) )
            ->caller( __METHOD__ )
            ->fetchResultSet();

        $newRevs = [];
        foreach ( $res as $row ) {
            $newRevs[] = $row->rev_id;
        }
        $this->output( "done.\n" );

        # Inform the user of what we're going to do
        $count = count( $newRevs );
        $this->output( "$count new revisions found.\n" );

        # Delete as appropriate
        if ( $delete && $count ) {
            $this->output( "Deleting..." );
            $dbw->newDeleteQueryBuilder()
                ->deleteFrom( 'revision' )
                ->where( [ 'rev_id' => $newRevs ] )
                ->caller( __METHOD__ )->execute();
            $dbw->newDeleteQueryBuilder()
                ->deleteFrom( 'ip_changes' )
                ->where( [ 'ipc_rev_id' => $newRevs ] )
                ->caller( __METHOD__ )->execute();
            $this->output( "done.\n" );
        }

        # Purge redundant text records
        $this->commitTransaction( $dbw, __METHOD__ );
        if ( $delete ) {
            $this->purgeRedundantText( true );
        }
    }
}

$maintClass = DeleteNewRevisions::class;
require_once RUN_MAINTENANCE_IF_MAIN;
