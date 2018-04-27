import PropTypes from 'prop-types';
import React from "react";
import cx from "classnames";

class FederatedSearchFieldContainer extends React.Component {

	render() {
		const { onNewSearch } = this.props;
		return (
			<div className="search-filters">
        <button className="search-filters__trigger">Filter Results</button>
        <form className="search-filters__form">
					<section className="search-accordion" role="region" aria-labelledby="section-title">
            <div className="search-filters__row">
              <h2 className="search-filters__title" id="section-title">Filter Results</h2>
            </div>

					<ul className="search-accordion__group">
						{this.props.children}
					</ul>
          </section>

          <div className="search-filters__row">
            <input className="search-filters__reset" type="button" defaultValue="Clear All" onClick={onNewSearch} />
          </div>
        </form>
			</div>
		);
	}
}

FederatedSearchFieldContainer.propTypes = {
	children: PropTypes.array,
	onNewSearch: PropTypes.func
};

export default FederatedSearchFieldContainer;