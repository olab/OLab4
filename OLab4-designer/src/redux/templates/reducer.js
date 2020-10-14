// @flow
import {
  type Templates as TemplatesType,
  type TemplatesActions,
  TEMPLATES_REQUESTED,
  TEMPLATES_REQUEST_FAILED,
  TEMPLATES_REQUEST_SUCCEEDED,
  TEMPLATE_UPLOAD_REQUESTED,
  TEMPLATE_UPLOAD_FULFILLED,
} from './types';

export const initialTemplatesState: TemplatesType = {
  list: [],
  isFetching: false,
  isUploading: false,
};

const templates = (state: TemplatesType = initialTemplatesState, action: TemplatesActions) => {
  switch (action.type) {
    case TEMPLATES_REQUESTED:
      return {
        ...state,
        isFetching: true,
      };
    case TEMPLATES_REQUEST_FAILED:
      return {
        ...state,
        isFetching: false,
      };
    case TEMPLATES_REQUEST_SUCCEEDED: {
      const { list, ...restState } = state;
      const { templates: diffTemplates } = action;

      return {
        ...restState,
        list: [
          ...list,
          ...diffTemplates,
        ],
        isFetching: false,
      };
    }
    case TEMPLATE_UPLOAD_REQUESTED:
      return {
        ...state,
        isUploading: true,
      };
    case TEMPLATE_UPLOAD_FULFILLED:
      return {
        ...state,
        isUploading: false,
      };
    default:
      return state;
  }
};

export default templates;
