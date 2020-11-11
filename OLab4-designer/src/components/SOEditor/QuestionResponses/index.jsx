// @flow
import React from 'react';
// import type { QuestionResponse } from '../../../redux/questionResponses/types';
import { EDITORS_FIELDS, QUESTION_TYPES } from '../config';
import { FieldLabel } from '../styles';
import { OtherContent, FullContainerWidth } from './styles';
import { SCOPED_OBJECTS } from '../../config';
import { withQuestionResponseRedux } from './index.service';
import CircularSpinnerWithText from '../../../shared/components/CircularSpinnerWithText';
import EditorWrapper from '../../../shared/components/EditorWrapper';
import OutlinedInput from '../../../shared/components/OutlinedInput';
import ScopedObjectService from '../index.service';
import Switch from '../../../shared/components/Switch';
import type { IScopedObjectProps } from './types';

class QuestionResponses extends ScopedObjectService {
  constructor(props: IScopedObjectProps) {
    super(props, SCOPED_OBJECTS.QUESTIONRESPONSES.name);
    this.state = {
      description: '',
      id: 0,
      isCorrect: false,
      isFieldsDisabled: false,
      name: '',
      questionId: 0,
      questionType: Number(Object.keys(QUESTION_TYPES)[0]),
      score: 0,
      text: '',
    };
  }

  render() {
    const {
      feedback,
      id,
      isCorrect,
      isFieldsDisabled,
      order,
      response,
      score,
    } = this.state;


    if (id === 0) {
      return <CircularSpinnerWithText text="Data is being fetched..." large centered />;
    }

    const idInfo = ` (Id: ${id})`;

    return (
      <EditorWrapper
        isEditMode={this.isEditMode}
        isDisabled={isFieldsDisabled}
        scopedObject={this.scopedObjectType}
        onSubmit={this.handleSubmitScopedObject}
        hasBackButton={false}
      >
        <FieldLabel>
          {EDITORS_FIELDS.RESPONSE}
          <small>
            {idInfo}
          </small>
          <OutlinedInput
            name="response"
            placeholder={EDITORS_FIELDS.RESPONSE}
            value={response}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        <FieldLabel>
          {EDITORS_FIELDS.FEEDBACK}
          <OutlinedInput
            name="feedback"
            placeholder={EDITORS_FIELDS.FEEDBACK}
            value={feedback}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        <OtherContent>
          <FullContainerWidth>
            <FieldLabel>
              {EDITORS_FIELDS.SCORE}
              <OutlinedInput
                classes="fullWidth"
                name="score"
                placeholder={EDITORS_FIELDS.SCORE}
                value={score}
                onChange={this.handleInputChange}
                disabled={isFieldsDisabled}
                fullWidth
              />
            </FieldLabel>
          </FullContainerWidth>
          <FullContainerWidth>
            <FieldLabel>
              {EDITORS_FIELDS.ORDER}
              <OutlinedInput
                classes="fullWidth"
                name="order"
                placeholder={EDITORS_FIELDS.ORDER}
                value={order}
                onChange={this.handleInputChange}
                disabled={isFieldsDisabled}
                fullWidth
              />
            </FieldLabel>
          </FullContainerWidth>
        </OtherContent>
        <FieldLabel>
          {EDITORS_FIELDS.IS_CORRECT}
          <Switch
            name="isCorrect"
            labelPlacement="start"
            checked={isCorrect}
            onChange={this.handleSliderOrSwitchChange}
            disabled={isFieldsDisabled}
          />
        </FieldLabel>

      </EditorWrapper>
    );
  }
}

export default withQuestionResponseRedux(QuestionResponses, SCOPED_OBJECTS.QUESTIONRESPONSES.name);
