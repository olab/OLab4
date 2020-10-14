// @flow
import {
  type MapDetails as MapDetailsType,
  GET_MAP_DETAILS_REQUESTED,
  GET_MAP_DETAILS_SUCCEEDED,
  GET_MAP_DETAILS_FAILED,
  UPDATE_MAP_DETAILS_REQUESTED,
} from './types';

export const ACTION_GET_MAP_DETAILS_FAILED = () => ({
  type: GET_MAP_DETAILS_FAILED,
});

export const ACTION_GET_MAP_DETAILS_SUCCEEDED = (mapDetails: MapDetailsType) => ({
  type: GET_MAP_DETAILS_SUCCEEDED,
  mapDetails,
});

export const ACTION_GET_MAP_DETAILS_REQUESTED = (mapId: string) => ({
  type: GET_MAP_DETAILS_REQUESTED,
  mapId,
});

export const ACTION_UPDATE_MAP_DETAILS_REQUESTED = (mapDetails: MapDetailsType) => ({
  type: UPDATE_MAP_DETAILS_REQUESTED,
  mapDetails,
});
