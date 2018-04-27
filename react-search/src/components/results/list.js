import PropTypes from 'prop-types';
import React from "react";

class FederatedResultList extends React.Component {

	render() {
		return (
			<ul className="search-results">
				{this.props.children}
			</ul>
		);
	}
}

FederatedResultList.propTypes = {
	children: PropTypes.array
};

export default FederatedResultList;