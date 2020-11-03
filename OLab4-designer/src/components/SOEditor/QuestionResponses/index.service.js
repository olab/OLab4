// @flow
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';

import type { QuestionResponse } from '../../../redux/questionResponses/types';
import * as actions from '../../../redux/questionResponses/action';
import styles from './styles';

export const withQuestionResponseRedux = (
  Component: ReactElement<IQuestionResponseEditorProps>,
) => {
  const mapStateToProps = (state, ownProps) => ({
    questionId: Number(ownProps.match.params.questionId),
    questionResponseId: Number(ownProps.match.params.questionResponseId),
  });

  const mapDispatchToProps = dispatch => ({
    ACTION_SCOPED_OBJECT_DETAILS_REQUESTED: (questionId: number, questionResponseId: number) => {
      dispatch(actions.ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(
        questionId,
        questionResponseId,
      ));
    },
    ACTION_SCOPED_OBJECT_CREATE_REQUESTED: (scopedObjectData: QuestionResponse) => {
      dispatch(actions.ACTION_SCOPED_OBJECT_CREATE_REQUESTED(
        scopedObjectData,
      ));
    },
    ACTION_SCOPED_OBJECT_UPDATE_REQUESTED: (
      questionResponseId: number,
      scopedObjectData: QuestionResponse,
    ) => {
      dispatch(actions.ACTION_SCOPED_OBJECT_UPDATE_REQUESTED(
        questionResponseId,
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
