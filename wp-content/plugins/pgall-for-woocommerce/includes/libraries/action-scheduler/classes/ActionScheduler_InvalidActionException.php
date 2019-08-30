<?php
class ActionScheduler_InvalidActionException extends \InvalidArgumentException implements ActionScheduler_Exception {
	public static function from_decoding_args( $action_id ) {
		$message = sprintf(
			__( 'Action [%s] has invalid arguments. It cannot be JSON decoded to an array.', 'action-scheduler' ),
			$action_id
		);

		return new static( $message );
	}
}
