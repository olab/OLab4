// @flow
import React from 'react';
import { ToggleButtonGroup, ToggleButton } from '@material-ui/lab';
import { Delete as DeleteIcon } from '@material-ui/icons';
import { Button, IconButton } from '@material-ui/core';
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
import type { IQuestionResponseProps } from './types';

class QuestionResponses extends ScopedObjectService {
  constructor(props: IQuestionResponseProps) {
    super(props, SCOPED_OBJECTS.QUESTION.name);
    this.state = {
      addIndex: -1,
      isFieldsDisabled: false,
      isDetailsFetching: true,
      questionId: 0,
      questionType: Number(Object.keys(QUESTION_TYPES)[0]),
      responses: [],
    };

    // log.setDefaultLevel('trace');
    this.setPageTitle();
  }

  buildId = (name: String, index: Number): void => `${name.toLowerCase()}-${index}`;

  onToggleButtonChange = (e: Event): void => {
    const { value: index } = e.target.parentNode.parentNode.parentNode.parentNode.children[0];
    const { innerHTML: stringValue } = e.target;
    const { state } = this;
    const responses = [...state.responses];

    const [value] = Object.keys(CORRECTNESS_TYPES).filter(
      (key) => CORRECTNESS_TYPES[key] === stringValue,
    );

    if (typeof value !== 'undefined') {
      if (value.length > 0) {
        const fieldName = 'isCorrect';
        responses[Number(index)][fieldName] = Number(value);
        log.debug(`${fieldName}[${index}] = ${value}`);
      }
    }

    this.setState({ responses });
  }

  onIntegerTextChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const [fieldName, index] = name.split('-');
    const { state } = this;
    const responses = [...state.responses];

    if (isPositiveInteger(value)) {
      const int = parseInt(value, 10);
      responses[index][fieldName] = int;
      log.debug(`${fieldName}[${index}] = ${value}`);
    }

    this.setState({ responses });
  }

  onTextChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const [fieldName, index] = name.split('-');
    const { state } = this;
    const responses = [...state.responses];

    responses[index][fieldName] = value;
    log.debug(`${fieldName}[${index}] = ${value}`);
    this.setState({ responses });
  }

  onItemDelete = (id): void => {
    // if (id < 0) {
    const {
      state,
    } = this;
    let responses = [...state.responses];
    responses = responses.filter((value) => value.id !== id);
    this.setState({ responses });
    // }
    // else {
    //   const {
    //     ACTION_SCOPED_OBJECT_DELETE_REQUESTED,
    //     ACTION_SCOPED_OBJECT_DETAILS_REQUESTED,
    //   } = this.props;

    //   ACTION_SCOPED_OBJECT_DELETE_REQUESTED(id, 'questionresponses');
    //   ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(questionId);
    // }
  }

  onClickCreate = (questionId) => {
    let { addIndex } = this.state;
    const {
      state,
    } = this;
    const responses = [...state.responses];
    const newResponse = {
      id: addIndex,
      name: '',
      description: '',
      response: '',
      score: 0,
      order: 0,
      questionId,
      isCorrect: 0,
    };

    addIndex -= 1;
    responses.push(newResponse);
    this.setState({ responses });
  };

  onClickRevert = () => {
    const { ACTION_SCOPED_OBJECT_DETAILS_REQUESTED } = this.props;
    const { id } = this.state;
    ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(id);
  }

  onClickUpdate = (): void => {
    const {
      isFieldsDisabled,
      isShowModal,
      ...scopedObjectData
    } = this.state;
    const {
      ACTION_SCOPED_OBJECT_CREATE_REQUESTED,
      ACTION_SCOPED_OBJECT_UPDATE_REQUESTED,
    } = this.props;

    if (this.isEditMode) {
      const { responses } = scopedObjectData;
      ACTION_SCOPED_OBJECT_UPDATE_REQUESTED(responses);
    } else if (this.scopeLevelObj) {
      const { id: parentId } = this.scopeLevelObj;
      Object.assign(scopedObjectData, { parentId });

      ACTION_SCOPED_OBJECT_CREATE_REQUESTED(scopedObjectData);
    }
  }

  render() {
    const {
      isDetailsFetching,
      isFieldsDisabled,
      responses,
    } = this.state;
    const { classes } = this.props;

    if (isDetailsFetching) {
      return <CircularSpinnerWithText text="Data is being fetched..." large centered />;
    }

    return (
      <>
        <EditorWrapper
          hasBackButton={false}
          isDisabled={isFieldsDisabled}
          isEditMode={this.isEditMode}
          onRevert={this.onClickRevert}
          onSubmit={this.onClickUpdate}
          scopedObject={this.scopedObjectType}
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
                    onChange={this.onTextChange}
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
                    onChange={this.onTextChange}
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
                    onChange={this.onIntegerTextChange}
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
                    onChange={this.onIntegerTextChange}
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
                  onChange={this.onToggleButtonChange}
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
              <FullContainerWidth>
                <FieldLabel>
                  {i === 0 && (
                    <>
                      <br />
                    </>
                  )}
                  <IconButton
                    size="small"
                    title={`Delete ${item.response}`}
                    aria-label="Delete Scoped Object"
                    onClick={() => this.onItemDelete(item.id)}
                    classes={{ root: classes.deleteIcon }}
                  >
                    <DeleteIcon />
                  </IconButton>
                </FieldLabel>
              </FullContainerWidth>
            </OtherContent>
          ))}
          <FieldLabel>
            <Button
              type="submit"
              variant="contained"
              color="primary"
              className={classes.submit}
              onClick={() => this.onClickCreate()}
            >
              Create
            </Button>
          </FieldLabel>
        </EditorWrapper>
      </>
    );
  }
}

export default withQuestionResponseRedux(
  QuestionResponses,
  SCOPED_OBJECTS.QUESTION.name,
);
