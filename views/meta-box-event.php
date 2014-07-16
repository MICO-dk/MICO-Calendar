<?php 
/**
 * If this is a new event, get the related ID from the url.
 */
global $pagenow;
if( $pagenow == 'post-new.php' && isset($_GET['relationshipid']) ){
	$relatedID = $_GET['relationshipid'];
} else {
	$relatedID = get_related_id();
}
?>
<?php $this->display_event_form(); ?>