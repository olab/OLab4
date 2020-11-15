// @flow
import type { QuestionResponse } from '../../../redux/questionResponses/types';
export type ScopedObject = {
  id: number,
  acl: string,
  name: string,
  wiki: string,
  scopeLevel: string,
  description: string,
  isShowEyeIcon: boolean,
  isDetailsFetching: boolean,
  details: null | QuestionResponse,
};

export type IQuestionResponseProps = {
  classes: {
    [props: string]: any,
  },
  ACTION_SCOPED_OBJECT_CREATE_REQUESTED: Function,
  ACTION_SCOPED_OBJECT_DELETE_REQUESTED: Function,
  ACTION_SCOPED_OBJECT_DETAILS_REQUESTED: Function,
  ACTION_SCOPED_OBJECT_UPDATE_REQUESTED: Function,
  history: any,
  isScopedObjectCreating: boolean,
  isScopedObjectUpdating: boolean,
  match: any,
  scopedObjects: Array<ScopedObjectType>,
};