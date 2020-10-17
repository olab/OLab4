import React from 'react';

export default class MultiChoiceLayoutTest extends React.Component {
  render() {
    const { responses } = this.props;
    const listItems = responses.map((item) => <small>{item.name}</small>);

    return (
      <>
        {listItems}
      </>
    );
  }
}
