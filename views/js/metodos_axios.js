
async function method_post(url, dataJson) {
    try {
        const result = await axios({
            method: "POST",
            url: url,
            data: dataJson,
            contentType: "application/json",
        })
        return result.data

    } catch (error) {
        console.log(error)
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