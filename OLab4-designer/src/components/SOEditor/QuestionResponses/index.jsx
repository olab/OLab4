// @flow
import React from 'react';
import { ToggleButtonGroup, ToggleButton } from '@material-ui/lab';
import log from 'loglevel';
import { EDITORS_FIELDS, QUESTION_TYPES, CORRECTNESS_TYPES } from '../config';
import { FieldLabel } from '../styles';
import { isPositiveInteger } from '../../../helpers/dataTypes';
import { OtherContent, FullContainerWidth } from './styles';
import { SCOPED_OBJECTS } from '../../config';
import { withQuestionResponseRedux } from './index.service';
import CircularSpinnerWithText from '../../../shared/components/CircularSpinnerWithText';
import EditorWrapper from '../../../shared/components/EditorWrapper';
import OutlinedInput from '../../../shared/components/OutlinedInput';
import ScopedObjectService from '../index.service';
import type { IScopedObjectProps } from './types';

class QuestionResponses extends ScopedObjectService {
  constructor(props: IScopedObjectProps) {
    super(props, SCOPED_OBJECTS.QUESTION.name);
    this.state = {
      description: '',
      id: 0,
      isCorrect: false,
      isFieldsDisabled: false,
      name: '',
      questionId: 0,
      questionType: Number(Object.keys(QUESTION_TYPES)[0]),
      responses: [],
      score: 0,
      text: '',
    };

    log.setDefaultLevel('trace');
  }

  buildId = (name: String, index: Number): void => `${name.toLowerCase()}-${index}`;

  handleToggleButtonChange = (e: Event): void => {
    const { value: index } = e.target.parentNode.parentNode.parentNode.parentNode.children[0];
    const { innerHTML: stringValue } = e.target;
    const responses = [...this.state.responses];

    const [value] = Object.keys(CORRECTNESS_TYPES).filter(
      (key) => CORRECTNESS_TYPES[key] === stringValue,
    );
    if (value.length > 0) {
      const fieldName = 'isCorrect';
      responses[Number(index)][fieldName] = Number(value);
      log.debug(`${fieldName}[${index}] = ${value}`);
    }

    this.setState({ responses });
  }

  handleIntegerInputChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const [fieldName, index] = name.split('-');
    const responses = [...this.state.responses];

    if (isPositiveInteger(value)) {
      const int = parseInt(value, 10);
      responses[index][fieldName] = int;
      log.debug(`${fieldName}[${index}] = ${value}`);
    }

    this.setState({ responses });
  }

  handleTextInputChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const [fieldName, index] = name.split('-');

    const responses = [...this.state.responses];
    responses[index][fieldName] = value;
    log.debug(`${fieldName}[${index}] = ${value}`);
    this.setState({ responses });
  }

  handleSubmitScopedObject = (): void => {
    const {
      isFieldsDisabled,
      isShowModal,
      ...scopedObjectData
    } = this.state;
    const {
      match: { params: { scopedObjectId: questionId } },
      ACTION_SCOPED_OBJECT_CREATE_REQUESTED,
      ACTION_SCOPED_OBJECT_UPDATE_REQUESTED,
    } = this.props;

    if (this.isEditMode) {
      ACTION_SCOPED_OBJECT_UPDATE_REQUESTED(Number(questionId), scopedObjectData);
    } else if (this.scopeLevelObj) {
      const { id: parentId } = this.scopeLevelObj;
      Object.assign(scopedObjectData, { parentId });

      ACTION_SCOPED_OBJECT_CREATE_REQUESTED(scopedObjectData);
    }
  }

  render() {
    const {
      id,
      isFieldsDisabled,
      responses,
    } = this.state;

    if (id === 0) {
      return <CircularSpinnerWithText text="Data is being fetched..." large centered />;
    }

    return (
      <EditorWrapper
        isEditMode={this.isEditMode}
        isDisabled={isFieldsDisabled}
        scopedObject={this.scopedObjectType}
        onSubmit={this.handleSubmitScopedObject}
        hasBackButton={false}
      >
        {responses.map((item, i) => (
          <OtherContent>
            <input value={i} type="hidden" />
            <FullContainerWidth>
              <FieldLabel>
                {i === 0 && (
                  <>
                    {EDITORS_FIELDS.RESPONSE}
                  </>
                )}
                <OutlinedInput
                  name={this.buildId('response', i)}
                  placeholder={EDITORS_FIELDS.RESPONSE}
                  value={item.response}
                  onChange={this.handleTextInputChange}
                  disabled={isFieldsDisabled}
                  fullWidth
                />
              </FieldLabel>
            </FullContainerWidth>
            <FullContainerWidth>
              <FieldLabel>
                {i === 0 && (
                  <>
                    {EDITORS_FIELDS.FEEDBACK}
                  </>
                )}
                <OutlinedInput
                  name={this.buildId('feedback', i)}
                  placeholder={EDITORS_FIELDS.FEEDBACK}
                  value={item.feedback}
                  onChange={this.handleTextInputChange}
                  disabled={isFieldsDisabled}
                  fullWidth
                />
              </FieldLabel>
            </FullContainerWidth>
            <FullContainerWidth>
              <FieldLabel>
                {i === 0 && (
                  <>
                    {EDITORS_FIELDS.SCORE}
                  </>
                )}
                <OutlinedInput
                  name={this.buildId('score', i)}
                  placeholder={EDITORS_FIELDS.SCORE}
                  value={item.score}
                  onChange={this.handleIntegerInputChange}
                  disabled={isFieldsDisabled}
                  fullWidth
                />
              </FieldLabel>
            </FullContainerWidth>
            <FullContainerWidth>
              <FieldLabel>
                {i === 0 && (
                  <>
                    {EDITORS_FIELDS.ORDER}
                  </>
                )}
                <OutlinedInput
                  name={this.buildId('order', i)}
                  placeholder={EDITORS_FIELDS.ORDER}
                  value={item.order}
                  onChange={this.handleIntegerInputChange}
                  disabled={isFieldsDisabled}
                  fullWidth
                />
              </FieldLabel>
            </FullContainerWidth>
            <FullContainerWidth>
              <FieldLabel>
                {i === 0 && (
                  <>
                    {EDITORS_FIELDS.IS_CORRECT}
                  </>
                )}
              </FieldLabel>
              <ToggleButtonGroup
                size="small"
                name="isCorrect"
                orientation="horizontal"
                value={Number(item.isCorrect)}
                exclusive
                onChange={this.handleToggleButtonChange}
              >
                <ToggleButton
                  name={this.buildId('incorrect', i)}
                  classes={{ root: this.props.classes.toggleButton }}
                  value={0}
                  aria-label="list"
                >
                  {CORRECTNESS_TYPES[0]}
                </ToggleButton>
                <ToggleButton
                  name={this.buildId('correct', i)}
                  classes={{ root: this.props.classes.toggleButton }}
                  value={1}
                  aria-label="module"
                >
                  {CORRECTNESS_TYPES[1]}
                </ToggleButton>
                <ToggleButton
                  name={this.buildId('neutral', i)}
                  classes={{ root: this.props.classes.toggleButton }}
                  value={2}
                  aria-label="quilt"
                >
                  {CORRECTNESS_TYPES[2]}
                </ToggleButton>
              </ToggleButtonGroup>
            </FullContainerWidth>
          </OtherContent>
        ))}
      </EditorWrapper>
    );
  }
}

export default withQuestionResponseRedux(QuestionResponses, SCOPED_OBJECTS.QUESTION.name);
