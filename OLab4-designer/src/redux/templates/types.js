// @flow
export type Template = {
  id: number | null,
  name: string,
  description: string,
};

export type Templates = {
  list: Array<Template>,
  isFetching: boolean,
  isUploading: boolean,
};

const TEMPLATES_REQUESTED = 'TEMPLATES_REQUESTED';
type TemplatesRequested = {
  type: 'TEMPLATES_REQUESTED',
};

const TEMPLATES_REQUEST_FAILED = 'TEMPLATES_REQUEST_FAILED';
type TemplatesRequestFailed = {
  type: 'TEMPLATES_REQUEST_FAILED',
};

const TEMPLATES_REQUEST_SUCCEEDED = 'TEMPLATES_REQUEST_SUCCEEDED';
type TemplatesRequestSucceeded = {
  type: 'TEMPLATES_REQUEST_SUCCEEDED',
  templates: Array<Template>,
};

const TEMPLATE_UPLOAD_REQUESTED = 'TEMPLATE_UPLOAD_REQUESTED';
type TemplateUploadRequested = {
  type: 'TEMPLATE_UPLOAD_REQUESTED',
  templateName: string,
};

const TEMPLATE_UPLOAD_FULFILLED = 'TEMPLATE_UPLOAD_FULFILLED';
type TemplateUploadFulfilled = {
  type: 'TEMPLATE_UPLOAD_FULFILLED',
};

export type TemplatesActions = TemplatesRequested |
  TemplatesRequestSucceeded | TemplatesRequestFailed |
  TemplateUploadRequested | TemplateUploadFulfilled;

export {
  TEMPLATES_REQUESTED,
  TEMPLATES_REQUEST_FAILED,
  TEMPLATES_REQUEST_SUCCEEDED,
  TEMPLATE_UPLOAD_REQUESTED,
  TEMPLATE_UPLOAD_FULFILLED,
};
