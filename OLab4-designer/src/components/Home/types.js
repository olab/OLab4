// @flow
import type { Template as TemplateType } from '../../redux/templates/types';
import type { ScopeLevel as ScopeLevelType } from '../../redux/scopeLevels/types';

export type IHomeProps = {
  classes: {
    [props: string]: any,
  },
  history: any,
  mapId: number | null,
  maps: Array<ScopeLevelType>,
  isMapFetching: boolean,
  isMapsFetching: boolean,
  showFooterButtons: boolean,
  ACTION_MAPS_REQUESTED: Function,
  ACTION_TEMPLATES_REQUESTED: Function,
  ACTION_CREATE_MAP_REQUESTED: Function,
  ACTION_GET_WHOLE_MAP_MIDDLEWARE: Function,
  templates: Array<TemplateType>,
  isTemplatesFetching: boolean,
};

export type IHomeState = {
  mapsFiltered: Array<ScopeLevelType>,
  isButtonsDisabled: boolean,
  isShowTemplatesListModal: boolean,
};
