// @flow
import isEqual from 'lodash.isequal';
import differenceWith from 'lodash.differencewith';

import {
  type Template as TemplateType,
  TEMPLATES_REQUESTED,
  TEMPLATES_REQUEST_FAILED,
  TEMPLATES_REQUEST_SUCCEEDED,
  TEMPLATE_UPLOAD_REQUESTED,
  TEMPLATE_UPLOAD_FULFILLED,
} from './types';

export const ACTION_TEMPLATES_REQUESTED = () => ({
  type: TEMPLATES_REQUESTED,
});

export const ACTION_TEMPLATES_REQUEST_FAILED = () => ({
  type: TEMPLATES_REQUEST_FAILED,
});

export const ACTION_TEMPLATES_REQUEST_SUCCEEDED = (
  oldTemplates: Array<TemplateType>,
  newTemplates: Array<TemplateType>,
) => {
  const diffTemplates = differenceWith(newTemplates, oldTemplates, isEqual);

  return {
    type: TEMPLATES_REQUEST_SUCCEEDED,
    templates: diffTemplates,
  };
};

export const ACTION_TEMPLATE_UPLOAD_REQUESTED = (templateName: string) => ({
  type: TEMPLATE_UPLOAD_REQUESTED,
  templateName,
});

export const ACTION_TEMPLATE_UPLOAD_FULFILLED = () => ({
  type: TEMPLATE_UPLOAD_FULFILLED,
});
