// @flow
import type { QuestionResponse } from '../../../redux/questionResponses/types';

export type IQuestionResponseEditorProps = {
  classes: {
    [props: string]: any,
  },
  ACTION_SCOPE_LEVELS_REQUESTED: Function,
  ACTION_SCOPED_OBJECT_CREATE_REQUESTED: Function,
  ACTION_SCOPED_OBJECT_DETAILS_REQUESTED: Function,
  ACTION_SCOPED_OBJECT_UPDATE_REQUESTED: Function,
  history: any,
  isScopedObjectCreating: boolean,
  isScopedObjectUpdating: boolean,
  match: any,
  scopedObjects: Array<QuestionResponse>,
};