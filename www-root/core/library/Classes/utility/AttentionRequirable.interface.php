<?php

/**
 * Used to identify objects which may require attention. Sole implentable method should indicate whether attention is required or not
 * @author Jonathan Fingland
 *
 */
interface AttentionRequirable {
	/**
	 * Returns true if the implemnting object requires attention
	 * @return boolean
	 */
	public function isAttentionRequired();
} 