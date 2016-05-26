/**
 * External dependencies
 */
import { combineReducers } from 'redux';
import get from 'lodash/get';
import assign from 'lodash/assign';

/**
 * Internal dependencies
 */
import {
	JETPACK_SITE_DATA_FETCH,
	JETPACK_SITE_DATA_FETCH_RECEIVE,
	JETPACK_SITE_DATA_FETCH_FAIL
} from 'state/action-types';

export const items = ( state = {}, action ) => {
	switch ( action.type ) {
		case JETPACK_SITE_DATA_FETCH_RECEIVE:
			return assign( {}, action.siteData );
		default:
			return state;
	}
};

export const initialRequestsState = {
	isFetchingSiteData: false
};

export const requests = ( state = initialRequestsState, action ) => {
	switch ( action.type ) {
		case JETPACK_SITE_DATA_FETCH:
			return assign( {}, state, {
				isFetchingSiteData: true
			} );
		case JETPACK_SITE_DATA_FETCH_FAIL:
		case JETPACK_SITE_DATA_FETCH_RECEIVE:
			return assign( {}, state, {
				isFetchingSiteData: false
			} );

		default:
			return state;
	}
};

export const reducer = combineReducers( {
	items,
	requests
} );

/**
 * Returns true if currently requesting site data. Otherwise false.
 * otherwise.
 *
 * @param  {Object}  siteData Current Data for this site.
 * @return {Boolean}          Whether site data is being requested
 */
export function isFetchingSiteData( siteData ) {
	return !!siteData.requests.isFetchingSiteData;
}

/**
 * Returns the plan of this site.
 * @param  {Object} siteData Current Data for this site.
 * @return {Object|Boolean}  Site plan
 */
export function getSitePlan( siteData ) {
	if ( 'string' === typeof siteData.items.data ) {
		let jsonData = JSON.parse( siteData.items.data );
		return jsonData.plan;
	}
	return false;
}