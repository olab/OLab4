// @flow
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';

import type { QuestionResponse } from '../../../redux/questionResponses/types';
import { toLowerCaseAndPlural } from '../utils';

import * as actions from '../../../redux/questionResponses/action';
import styles from './styles';

export const withQuestionResponseRedux = (
  Component: ReactElement<IQuestionResponseEditorProps>,
  scopedObjectType: string,
) => {
  const mapStateToProps = ({ scopedObjects }) => ({
    scopedObjects: scopedObjectType.scopedObjectType,
    isCreating: scopedObjectType.isCreating,
    isUpdating: scopedObjectType.isUpdating,
    isFetching: scopedObjectType.isFetching,
    temp: scopedObjects,
  });

  const mapDispatchToProps = dispatch => ({
    ACTION_SCOPED_OBJECT_DETAILS_REQUESTED: (questionId: number, questionResponseId: number) => {
      dispatch(actions.ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(
        questionId,
        questionResponseId,
        toLowerCaseAndPlural(scopedObjectType),
      ));
    },
    ACTION_SCOPED_OBJECT_CREATE_REQUESTED: (scopedObjectData: QuestionResponse) => {
      dispatch(actions.ACTION_SCOPED_OBJECT_CREATE_REQUESTED(
        toLowerCaseAndPlural(scopedObjectType),
        scopedObjectData,
      ));
    },
    ACTION_SCOPED_OBJECT_UPDATE_REQUESTED: (
      scopedObjectId: number,
      scopedObjectData: QuestionResponse,
    ) => {
      dispatch(actions.ACTION_SCOPED_OBJECT_UPDATE_REQUESTED(
        scopedObjectId,
        toLowerCaseAndPlural(scopedObjectType),
        scopedObjectData,
      ));
    },
  });

  return connect(
    mapStateToProps,
    mapDispatchToProps,
  )(
    withStyles(styles)(
      withRouter(Component),
    ),
  );
};

export default withQuestionResponseRedux;
