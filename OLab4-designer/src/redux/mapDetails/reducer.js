// @flow
import {
  type MapDetailsActions,
  type MapDetails as MapDetailsType,
  GET_MAP_DETAILS_FAILED,
  GET_MAP_DETAILS_SUCCEEDED,
  GET_MAP_DETAILS_REQUESTED,
  UPDATE_MAP_DETAILS_REQUESTED,
} from './types';
import { CREATE_MAP_REQUESTED, CREATE_MAP_SUCCEEDED, CREATE_MAP_FAILED } from '../map/types';

export const initialMapDetailsState: MapDetailsType = {
  id: null,
  themeId: 1,
  securityType: 1,
  name: 'New Map',
  notes: '',
  author: '',
  keywords: '',
  description: '',
  isEnabled: false,
  isFetching: false,
  isTemplate: false,
  isLinkLogicVerified: false,
  isSendXapiStatements: false,
  isNodeContentVerified: false,
  isMediaContentComplete: false,
  isMediaCopyrightVerified: false,
  isInstructorGuideComplete: false,
  themes: [],
};

const mapDetails = (state: MapDetailsType = initialMapDetailsState, action: MapDetailsActions) => {
  switch (action.type) {
    case CREATE_MAP_REQUESTED:
    case GET_MAP_DETAILS_REQUESTED:
      return {
        ...state,
        isFetching: true,
      };
    case CREATE_MAP_FAILED:
    case CREATE_MAP_SUCCEEDED:
    case GET_MAP_DETAILS_FAILED:
      return {
        ...state,
        isFetching: false,
      };
    case UPDATE_MAP_DETAILS_REQUESTED:
    case GET_MAP_DETAILS_SUCCEEDED: {
      const { mapDetails: newMapDetails } = action;

      return {
        ...state,
        ...newMapDetails,
        isFetching: false,
      };
    }
    default:
      return state;
  }
};

export default mapDetails;
