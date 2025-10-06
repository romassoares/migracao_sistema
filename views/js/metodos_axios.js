
async function method_post(url, data) {
    try {
        const isFormData = data instanceof FormData;

        const result = await axios({
            method: "POST",
            url: url,
            data: data,
            headers: isFormData
                ? { 'Content-Type': 'multipart/form-data' }
                : { 'Content-Type': 'application/json' },
        });

        return result.data;
    } catch (error) {
        console.error('Erro no method_post:', error);
        throw error;
    }
}

async function method_get(url) {
    try {
        const result = await axios({
            method: "GET",
            url: url,
        })
        return result.data

    } catch (error) {
        console.log(error)
    }
}