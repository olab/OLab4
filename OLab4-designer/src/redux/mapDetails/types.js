// @flow
export type Themes = {
  id: number,
  name: string,
  description: string,
};

export type MapDetails = {
  id: number,
  themeId: number,
  securityType: number,
  name: string,
  notes: string,
  author: string,
  keywords: string,
  description: string,
  isEnabled: boolean,
  isFetching: boolean,
  isTemplate: boolean,
  isLinkLogicVerified: boolean,
  isSendXapiStatements: boolean,
  isNodeContentVerified: boolean,
  isMediaContentComplete: boolean,
  isMediaCopyrightVerified: boolean,
  isInstructorGuideComplete: boolean,
  themes: Array<Themes>,
};

const GET_MAP_DETAILS_SUCCEEDED = 'GET_MAP_DETAILS_SUCCEEDED';
type GetMapDetailsSucceeded = {
  type: 'GET_MAP_DETAILS_SUCCEEDED',
  mapDetails: MapDetails,
};

const GET_MAP_DETAILS_FAILED = 'GET_MAP_DETAILS_FAILED';
type GetMapDetailsFailed = {
  type: 'GET_MAP_DETAILS_FAILED',
};

const GET_MAP_DETAILS_REQUESTED = 'GET_MAP_DETAILS_REQUESTED';
type GetMapDetailsRequested = {
  type: 'GET_MAP_DETAILS_REQUESTED',
  mapId: string,
};

const UPDATE_MAP_DETAILS_REQUESTED = 'UPDATE_MAP_DETAILS_REQUESTED';
type UpdateMapDetailsRequested = {
  type: 'UPDATE_MAP_DETAILS_REQUESTED',
  mapDetails: MapDetails,
};

export type MapDetailsActions = GetMapDetailsSucceeded | GetMapDetailsFailed |
  GetMapDetailsRequested | UpdateMapDetailsRequested;

export {
  GET_MAP_DETAILS_FAILED,
  GET_MAP_DETAILS_SUCCEEDED,
  GET_MAP_DETAILS_REQUESTED,
  UPDATE_MAP_DETAILS_REQUESTED,
};
