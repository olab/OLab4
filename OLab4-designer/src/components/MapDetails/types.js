// @flow
import type { MapDetails } from '../../redux/mapDetails/types';

export type MapDetailsProps = {
  classes: {
    [prop: string]: any,
  },
  match: any,
  mapIdUrl: string,
  mapDetails: MapDetails,
  themesNames: Array<string>,
  ACTION_GET_MAP_DETAILS_REQUESTED: Function,
  ACTION_UPDATE_MAP_DETAILS_REQUESTED: Function,
};

export type MapDetailsState = MapDetails;
