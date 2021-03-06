<?php
/**
 * Mapper Class.
 *
 * Keeps a WordPress Entity synced with a CiviCRM Entity via ACF Fields.
 *
 * @package CiviCRM_ACF_Integration
 * @since 0.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM ACF Integration Mapper Class.
 *
 * A class that encapsulates methods to keep a WordPress Entity synced with a
 * CiviCRM Entity via ACF Fields.
 *
 * @since 0.2
 */
class CiviCRM_ACF_Integration_Mapper {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.2
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Define date format mappings (CiviCRM to ACF).
	 *
	 * @since 0.3
	 * @access public
	 * @var array $date_mappings The CiviCRM to ACF date format mappings.
	 */
	public $date_mappings = [
		'mm/dd/yy' => 'm/d/Y',
		'dd/mm/yy' => 'd/m/Y',
		'yy-mm-dd' => 'Y-m-d',
		'dd-mm-yy' => 'd-m-Y',
		'dd.mm.yy' => 'd.m.Y',
		'M d, yy' => 'M d, Y',
		'd M yy' => 'j M Y',
		'MM d, yy' => 'F j, Y',
		'd MM yy' => 'd F Y',
		'DD, d MM yy' => 'l, d F Y',
		'mm/dd' => 'm/d',
		'dd-mm' => 'd-m',
		'M yy' => 'm Y',
		'yy' => 'Y',
	];

	/**
	 * Define time format mappings (CiviCRM to ACF).
	 *
	 * @since 0.3
	 * @access public
	 * @var array $time_mappings The CiviCRM to ACF time format mappings.
	 */
	public $time_mappings = [
		'1' => 'g:i a',
		'2' => 'H:i',
	];



	/**
	 * Constructor.
	 *
	 * @since 0.2
	 *
	 * @param object $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Init when this plugin is loaded.
		add_action( 'civicrm_acf_integration_loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.2
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.2
	 */
	public function register_hooks() {

		// Register WordPress hooks.
		$this->hooks_wordpress_add();

		// Register CiviCRM hooks.
		$this->hooks_civicrm_add();

	}



	// -------------------------------------------------------------------------



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.2.1
	 */
	public function hooks_wordpress_add() {

		// Intercept Post update in WordPress super-early.
		add_action( 'save_post', [ $this, 'post_saved' ], 1, 3 );

		// Intercept ACF fields prior to save.
		//add_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 5, 1 );

		// Intercept ACF fields after save.
		add_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 20, 1 );

	}



	/**
	 * Remove WordPress hooks.
	 *
	 * @since 0.2.1
	 */
	public function hooks_wordpress_remove() {

		// Remove Post update hook.
		remove_action( 'save_post', [ $this, 'post_saved' ], 1 );

		// Remove ACF fields update hook.
		//remove_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 5 );

		// Remove ACF fields update hook.
		remove_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 20 );

	}



	/**
	 * Register CiviCRM hooks.
	 *
	 * @since 0.2.1
	 */
	public function hooks_civicrm_add() {

		// Intercept Contact updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'contact_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'contact_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'contact_edited' ], 10, 4 );

		// Intercept Email updates in CiviCRM.
		add_action( 'civicrm_post', [ $this, 'email_edited' ], 10, 4 );

		// Intercept Website updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'website_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'website_edited' ], 10, 4 );

		// Intercept Relationship updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'relationship_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'relationship_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'relationship_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'relationship_deleted' ], 10, 4 );

		// Intercept Address updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'address_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'address_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'address_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'address_deleted' ], 10, 4 );

		// Intercept CiviCRM Custom Table updates.
		add_action( 'civicrm_custom', [ $this, 'custom_edited' ], 10, 4 );

	}



	/**
	 * Remove CiviCRM hooks.
	 *
	 * @since 0.2.1
	 */
	public function hooks_civicrm_remove() {

		// Remove Contact update hooks.
		remove_action( 'civicrm_pre', [ $this, 'contact_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'contact_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'contact_edited' ], 10 );

		// Remove Email update hooks.
		remove_action( 'civicrm_post', [ $this, 'email_edited' ], 10 );

		// Remove Website update hooks.
		remove_action( 'civicrm_pre', [ $this, 'website_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'website_edited' ], 10 );

		// Remove Relationship update hooks.
		remove_action( 'civicrm_pre', [ $this, 'relationship_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'relationship_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'relationship_edited' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'relationship_deleted' ], 10 );

		// Remove Address update hooks.
		remove_action( 'civicrm_pre', [ $this, 'address_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'address_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'address_edited' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'address_deleted' ], 10 );

		// Remove CiviCRM Custom Table hooks.
		remove_action( 'civicrm_custom', [ $this, 'custom_edited' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Fires just before a CiviCRM Entity is created, updated or deleted.
	 *
	 * We don't use this at present but it's useful for debugging.
	 *
	 * @since 0.2.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_pre_edit( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Contact.
		$top_level_types = $this->plugin->civicrm->contact_type->types_get_top_level();
		if ( ! in_array( $objectName, $top_level_types ) ) {
			return;
		}

		// Bail if this Contact's Contact Type is not mapped.
		$contact_types = $this->plugin->civicrm->contact_type->hierarchy_get_for_contact( $objectRef );
		$post_type = $this->plugin->civicrm->contact_type->is_mapped( $contact_types );
		if ( $post_type === false ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			'top_level_types' => $top_level_types,
			'contact_types' => $contact_types,
			'post_type' => $post_type,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a relevant Contact is about to be created, updated or deleted.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_contact_pre', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	/**
	 * Create a WordPress Post when a CiviCRM Contact is created.
	 *
	 * @since 0.4.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_created( $op, $objectName, $objectId, $objectRef ) {

		// Bail if it's not the "create" operation.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if it's not a Contact.
		if ( ! ( $objectRef instanceof CRM_Contact_DAO_Contact ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a relevant Contact has been created.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_contact_created', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	/**
	 * Update a WordPress Post when a CiviCRM Contact is updated.
	 *
	 * @since 0.2.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_edited( $op, $objectName, $objectId, $objectRef ) {

		// Bail if it's not an "edit" operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if it's not a Contact.
		if ( ! ( $objectRef instanceof CRM_Contact_DAO_Contact ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a relevant Contact has been updated.
		 *
		 * Used internally to:
		 *
		 * - Update a WordPress Post
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_contact_edited', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	/**
	 * Intercept when a CiviCRM Email is updated.
	 *
	 * @since 0.4.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function email_edited( $op, $objectName, $objectId, $objectRef ) {

		// Bail if this is not an Email.
		if ( $objectName != 'Email' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a CiviCRM Email has been updated.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_email_edited', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Website is about to be edited.
	 *
	 * @since 0.4.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function website_pre_edit( $op, $objectName, $objectId, $objectRef ) {

		// Bail if this is not a Website.
		if ( $objectName != 'Website' ) {
			return;
		}

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a CiviCRM Website is about to be updated.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_website_pre_edit', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	/**
	 * Intercept when a CiviCRM Website is updated.
	 *
	 * @since 0.4.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function website_edited( $op, $objectName, $objectId, $objectRef ) {

		// Bail if this is not a Website.
		if ( $objectName != 'Website' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a CiviCRM Website has been updated.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_website_edited', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept Custom Field updates.
	 *
	 * @since 0.3
	 *
	 * @param str $op The kind of operation.
	 * @param int $groupID The numeric ID of the Custom Group.
	 * @param int $entityID The numeric ID of the Contact.
	 * @param array $custom_fields The array of Custom Fields.
	 */
	public function custom_edited( $op, $groupID, $entityID, &$custom_fields ) {

		// Bail if there's nothing to see here.
		if ( empty( $custom_fields ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'groupID' => $groupID,
			'entityID' => $entityID,
			'custom_fields' => $custom_fields,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a set of CiviCRM Custom Fields has been updated.
		 *
		 * Used internally to:
		 *
		 * - Update a WordPress Post
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_custom_edited', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Relationship is about to be edited.
	 *
	 * @since 0.4.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function relationship_pre_edit( $op, $objectName, $objectId, $objectRef ) {

		// Bail if this is not a Relationship.
		if ( $objectName != 'Relationship' ) {
			return;
		}

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a CiviCRM Relationship is about to be updated.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_relationship_pre_edit', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	/**
	 * Intercept when a CiviCRM Contact's Relationship has been created.
	 *
	 * @since 0.4.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function relationship_created( $op, $objectName, $objectId, $objectRef ) {

		// Bail if this is not a Relationship.
		if ( $objectName != 'Relationship' ) {
			return;
		}

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a CiviCRM Relationship has been created.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_relationship_created', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	/**
	 * Intercept when a CiviCRM Contact's Relationship has been updated.
	 *
	 * @since 0.4.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function relationship_edited( $op, $objectName, $objectId, $objectRef ) {

		// Bail if this is not a Relationship.
		if ( $objectName != 'Relationship' ) {
			return;
		}

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a CiviCRM Contact's Relationship has been updated.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_relationship_edited', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	/**
	 * Intercept when a CiviCRM Contact's Relationship has been deleted.
	 *
	 * @since 0.4.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function relationship_deleted( $op, $objectName, $objectId, $objectRef ) {

		// Bail if this is not a Relationship.
		if ( $objectName != 'Relationship' ) {
			return;
		}

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a CiviCRM Relationship has been deleted.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_relationship_deleted', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Address is about to be edited.
	 *
	 * @since 0.4.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function address_pre_edit( $op, $objectName, $objectId, $objectRef ) {

		// Bail if this is not a Address.
		if ( $objectName != 'Address' ) {
			return;
		}

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a CiviCRM Address is about to be updated.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_address_pre_edit', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	/**
	 * Intercept when a CiviCRM Contact's Address has been created.
	 *
	 * @since 0.4.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function address_created( $op, $objectName, $objectId, $objectRef ) {

		// Bail if this is not a Address.
		if ( $objectName != 'Address' ) {
			return;
		}

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a CiviCRM Address has been created.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_address_created', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	/**
	 * Intercept when a CiviCRM Contact's Address has been edited.
	 *
	 * @since 0.4.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function address_edited( $op, $objectName, $objectId, $objectRef ) {

		// Bail if this is not a Address.
		if ( $objectName != 'Address' ) {
			return;
		}

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a CiviCRM Address has been updated.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_address_edited', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	/**
	 * Intercept when a CiviCRM Contact's Address has been deleted.
	 *
	 * @since 0.4.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function address_deleted( $op, $objectName, $objectId, $objectRef ) {

		// Bail if this is not a Address.
		if ( $objectName != 'Address' ) {
			return;
		}

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->hooks_wordpress_remove();

		/**
		 * Broadcast that a CiviCRM Address has been deleted.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_address_deleted', $args );

		// Reinstate WordPress callbacks.
		$this->hooks_wordpress_add();

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept the Post saved operation.
	 *
	 * @since 0.2
	 *
	 * @param int $post_id The ID of the Post or revision.
	 * @param int $post The Post object.
	 * @param bool $update True if the Post is being updated, false if new.
	 */
	public function post_saved( $post_id, $post, $update ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() AND ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'post_id' => $post_id,
			'post' => $post,
			'update' => $update,
		];

		// Remove CiviCRM callbacks to prevent recursion.
		$this->hooks_civicrm_remove();

		/**
		 * Broadcast that a WordPress Post has been saved.
		 *
		 * @since 0.4.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_post_saved', $args );

		// Reinstate CiviCRM callbacks.
		$this->hooks_civicrm_add();

	}



	/**
	 * Intercept the ACF Fields saved operation.
	 *
	 * @since 0.2
	 *
	 * @param integer $post_id The ID of the Post or revision.
	 */
	public function acf_fields_saved( $post_id ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() AND ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'post_id' => $post_id,
		];

		// Remove CiviCRM callbacks to prevent recursion.
		$this->hooks_civicrm_remove();

		/**
		 * Broadcast that ACF Fields have been saved for a Post.
		 *
		 * @since 0.4.5
		 * @since 0.6.1 Params reduced to array.
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'civicrm_acf_integration_mapper_acf_fields_saved', $args );

		// Reinstate CiviCRM callbacks.
		$this->hooks_civicrm_add();

	}



} // Class ends.



