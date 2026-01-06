class ParameterManager {
    constructor() {
        this.baseUrl = window.location.origin + '/CIMS/Ajax/php/parameter.php';
    }

    /**
     * Get a parameter value
     * @param {string} key - The parameter key to retrieve
     * @returns {Promise<string>} The parameter value
     */
    async get(key) {
        try {
            const response = await $.ajax({
                url: this.baseUrl,
                type: 'GET',
                data: { action: 'get', key: key },
                dataType: 'json'
            });

            if (response.status === 'success') {
                return response.data;
            } else {
                throw new Error(response.message || 'Failed to get parameter');
            }
        } catch (error) {
            console.error('Error getting parameter:', error);
            throw error;
        }
    }

    /**
     * Increment a numeric parameter value
     * @param {string} key - The parameter key to increment
     * @param {number} [incrementBy=1] - Amount to increment by
     * @returns {Promise<string>} The new value after increment
     */
    async increment(key, incrementBy = 1) {
        try {
            const response = await $.ajax({
                url: this.baseUrl,
                type: 'POST',
                data: { 
                    action: 'increment',
                    key: key,
                    increment_by: incrementBy
                },
                dataType: 'json'
            });

            if (response.status === 'success') {
                return response.data;
            } else {
                throw new Error(response.message || 'Failed to increment parameter');
            }
        } catch (error) {
            console.error('Error incrementing parameter:', error);
            throw error;
        }
    }
}

// Create a global instance
const parameterManager = new ParameterManager();
