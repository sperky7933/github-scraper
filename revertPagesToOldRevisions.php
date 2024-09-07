<?php
/**
 * Revert pages to the latest revision before August 9, 2019
 *
 * @file
 * @ingroup Maintenance
 */

require_once __DIR__ . '/Maintenance.php';

/**
 * Maintenance script to revert pages to their state before August 9, 2019.
 *
 * @ingroup Maintenance
 */
class RevertPagesToOldRevisions extends Maintenance {
    public function __construct() {
        parent::__construct();
        $this->addDescription( 'Revert pages to the latest revision before August 9, 2019' );
        $this->addOption( 'delete', 'Actually perform the reversion' );
        $this->addOption( 'page_id', 'List of page ids to work on', false );
    }

    public function execute() {
        $this->output( "Reverting pages to their state on August 9, 2019\n\n" );
        $this->doRevert( $this->hasOption( 'delete' ), $this->getArgs() );
    }

    private function doRevert( $delete = false, $pageIds = [] ) {
        $dbw = $this->getPrimaryDB();
        $this->beginTransaction( $dbw, __METHOD__ );

        $pageConds = [];

        // Limit to specific page IDs if provided
        if ( count( $pageIds ) > 0 ) {
            $pageConds['page_id'] = $pageIds;
            $this->output( "Limiting to page IDs " . implode( ',', $pageIds ) . "\n" );
        }

        // Get all pages to update
        $this->output( "Fetching pages..." );
        $pages = $dbw->newSelectQueryBuilder()
            ->select( 'page_id' )
            ->from( 'page' )
            ->where( $pageConds )
            ->caller( __METHOD__ )
            ->fetchResultSet();
        $this->output( "done.\n" );

        // Loop over pages and find the latest revision before August 9, 2019
        foreach ( $pages as $page ) {
            $pageId = $page->page_id;

            $this->output( "Finding latest revision before August 9, 2019 for page_id: $pageId\n" );

            // Find the latest revision for this page before August 9, 2019
            $oldRev = $dbw->newSelectQueryBuilder()
                ->select( 'rev_id' )
                ->from( 'revision' )
                ->where( [
                    'rev_page' => $pageId,
                    'rev_timestamp < ' . $dbw->addQuotes('20190809000000') // Revisions before August 9, 2019
                ] )
                ->orderBy( 'rev_timestamp', 'DESC' )
                ->limit( 1 ) // Get the latest revision before the cutoff
                ->caller( __METHOD__ )
                ->fetchField();

            if ( $oldRev ) {
                $this->output( "Latest revision before the date is rev_id: $oldRev\n" );
                if ( $delete ) {
                    $this->output( "Reverting page $pageId to revision $oldRev...\n" );
                    // Update the page_latest field to the old revision
                    $dbw->newUpdateQueryBuilder()
                        ->update( 'page' )
                        ->set( [ 'page_latest' => $oldRev ] )
                        ->where( [ 'page_id' => $pageId ] )
                        ->caller( __METHOD__ )
                        ->execute();
                }
            } else {
                $this->output( "No revision before August 9, 2019 found for page_id: $pageId\n" );
            }
        }

        // Commit transaction
        if ( $delete ) {
            $this->commitTransaction( $dbw, __METHOD__ );
            $this->output( "All pages reverted successfully.\n" );
        } else {
            $this->output( "No changes made (run with --delete to perform the reversion).\n" );
        }
    }
}

$maintClass = RevertPagesToOldRevisions::class;
require_once RUN_MAINTENANCE_IF_MAIN;

