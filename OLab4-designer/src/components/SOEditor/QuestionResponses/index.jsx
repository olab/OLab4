// @flow
import React, { PureComponent } from 'react';
// import { withRouter } from 'react-router-dom';
// import { connect } from 'react-redux';
// import { withStyles } from '@material-ui/core/styles';

import { withQuestionResponseRedux } from './index.service';
import CircularSpinnerWithText from '../../../shared/components/CircularSpinnerWithText';

import { PAGE_TITLES } from '../../config';

import type { IQuestionResponseEditorProps } from './types';
import type { QuestionResponse } from '../../../redux/questionResponses/types';
import OutlinedInput from '../../../shared/components/OutlinedInput';
import EditorWrapper from '../../../shared/components/EditorWrapper';
import Switch from '../../../shared/components/Switch';

// import styles from './styles';

import { FieldLabel, SwitchWrapper } from '../styles';
import { EDITORS_FIELDS } from '../config';

class QuestionResponses extends PureComponent<IQuestionResponseEditorProps, QuestionResponse> {
  constructor(props: IQuestionResponseEditorProps) {
    super(props);

    this.checkIfEditMode();
    this.setPageTitle();
  }

  setPageTitle = (): void => {
    const title = this.isEditMode ? PAGE_TITLES.EDIT_SO : PAGE_TITLES.ADD_SO;
    document.title = title(this.scopedObjectType);
  }

  checkIfEditMode = (): void => {
    const {
      match: {
        params: {
          questionId,
          questionResponseId,
        },
      },
      ACTION_SCOPED_OBJECT_DETAILS_REQUESTED,
    } = this.props;

    ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(Number(questionId), Number(questionResponseId));
    this.isEditMode = true;
  }

  onSwitchChange = (e: Event, value: number | boolean, name: string): void => {
    this.setState({ [name]: value });
  };

  render() {
    const {
      scopedObjects,
    } = this.props;

    if ((scopedObjects.isFetching)
      || (scopedObjects.isFetching === null)
      || (scopedObjects.questionresponses.length === 0)) {
      return <CircularSpinnerWithText text="Data is being fetched..." large centered />;
    }

    const questionResponse = scopedObjects.questionresponses[0];
    const idInfo = ` (Id: ${questionResponse.id})`;
    const isFieldsDisabled = false;
    const isEditMode = true;
    const scopedObjectType = 'question response';

    return (
      <EditorWrapper
        isEditMode={isEditMode}
        isDisabled={isFieldsDisabled}
        scopedObject={scopedObjectType}
        onSubmit={this.handleSubmitScopedObject}
      >
        <FieldLabel>
          {EDITORS_FIELDS.NAME}
          <small>
            {idInfo}
          </small>
          <OutlinedInput
            name="name"
            placeholder={EDITORS_FIELDS.NAME}
            value={questionResponse.name}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        <FieldLabel>
          {EDITORS_FIELDS.DESCRIPTION}
          <OutlinedInput
            name="name"
            placeholder={EDITORS_FIELDS.DESCRIPTION}
            value={questionResponse.description}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        <FieldLabel>
          {EDITORS_FIELDS.DESCRIPTION}
          <OutlinedInput
            name="name"
            placeholder={EDITORS_FIELDS.DESCRIPTION}
            value={questionResponse.text}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>

        <FieldLabel>
          {EDITORS_FIELDS.SCORE}
          <OutlinedInput
            name="score"
            placeholder={EDITORS_FIELDS.SCORE}
            value={questionResponse.score}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>

        <FieldLabel>
          {EDITORS_FIELDS.ORDER}
          <OutlinedInput
            name="order"
            placeholder={EDITORS_FIELDS.ORDER}
            value={questionResponse.order}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>

        <SwitchWrapper>
          <Switch
            name="isCorrect"
            label={EDITORS_FIELDS.IS_CORRECT}
            labelPlacement="start"
            checked={questionResponse.isCorrect}
            onChange={this.onSwitchChange}
            disabled={isFieldsDisabled}
          />
        </SwitchWrapper>

      </EditorWrapper>
    );
  }
}

export default withQuestionResponseRedux(
  QuestionResponses,
);
